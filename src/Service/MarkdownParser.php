<?php

namespace App\Service;

class MarkdownParser
{
    public function toHtml(string $markdown): string
    {
        $lines = explode("\n", $markdown);
        $html = '';
        $inCodeBlock = false;
        $inTable = false;
        $inList = false;
        $inBlockquote = false;
        /** @var list<string> $blockquoteLines */
        $blockquoteLines = [];

        foreach ($lines as $line) {
            // Code blocks
            if (str_starts_with(trim($line), '```')) {
                if ($inCodeBlock) {
                    $html .= '</code></pre>';
                    $inCodeBlock = false;
                } else {
                    if ($inList) {
                        $html .= '</ul>';
                        $inList = false;
                    }
                    if ($inBlockquote) {
                        $html .= $this->renderBlockquote($blockquoteLines);
                        $blockquoteLines = [];
                        $inBlockquote = false;
                    }
                    $lang = trim(substr(trim($line), 3));
                    $html .= '<pre class="roadmap-code"><code' . ($lang !== '' ? ' class="language-' . htmlspecialchars($lang) . '"' : '') . '>';
                    $inCodeBlock = true;
                }

                continue;
            }

            if ($inCodeBlock) {
                $html .= htmlspecialchars($line) . "\n";

                continue;
            }

            // Blockquotes
            if (str_starts_with($line, '> ') || $line === '>') {
                if ($inList) {
                    $html .= '</ul>';
                    $inList = false;
                }
                if ($inTable) {
                    $html .= '</tbody></table></div>';
                    $inTable = false;
                }
                $inBlockquote = true;
                $blockquoteLines[] = ltrim(substr($line, 1));

                continue;
            }

            if ($inBlockquote && !str_starts_with($line, '> ')) {
                $html .= $this->renderBlockquote($blockquoteLines);
                $blockquoteLines = [];
                $inBlockquote = false;
            }

            // Horizontal rule
            if (preg_match('/^-{3,}$/', trim($line))) {
                if ($inList) {
                    $html .= '</ul>';
                    $inList = false;
                }
                if ($inTable) {
                    $html .= '</tbody></table></div>';
                    $inTable = false;
                }
                $html .= '<hr class="roadmap-hr">';

                continue;
            }

            // Headings
            if (preg_match('/^(#{1,6})\s+(.+)$/', $line, $m)) {
                if ($inList) {
                    $html .= '</ul>';
                    $inList = false;
                }
                if ($inTable) {
                    $html .= '</tbody></table></div>';
                    $inTable = false;
                }
                $level = strlen($m[1]);
                $text = $this->inlineFormat($m[2]);
                $html .= '<h' . $level . ' class="roadmap-h' . $level . '">' . $text . '</h' . $level . '>';

                continue;
            }

            // Table rows
            if (str_contains($line, '|') && preg_match('/^\|(.+)\|$/', trim($line))) {
                $cells = array_map('trim', explode('|', trim(trim($line), '|')));

                // Skip separator rows
                if (\count($cells) > 0 && preg_match('/^[-:\s]+$/', $cells[0])) {
                    continue;
                }

                if (!$inTable) {
                    if ($inList) {
                        $html .= '</ul>';
                        $inList = false;
                    }
                    $inTable = true;
                    $html .= '<div class="roadmap-table-wrapper"><table class="roadmap-table"><thead><tr>';
                    foreach ($cells as $cell) {
                        $html .= '<th>' . $this->inlineFormat($cell) . '</th>';
                    }
                    $html .= '</tr></thead><tbody>';

                    continue;
                }

                $html .= '<tr>';
                foreach ($cells as $cell) {
                    $html .= '<td>' . $this->inlineFormat($cell) . '</td>';
                }
                $html .= '</tr>';

                continue;
            }

            if ($inTable) {
                $html .= '</tbody></table></div>';
                $inTable = false;
            }

            // Checkbox list items
            if (preg_match('/^(\s*)- \[([ xX])\]\s+(.+)$/', $line, $m)) {
                if (!$inList) {
                    $inList = true;
                    $html .= '<ul class="roadmap-checklist">';
                }
                $checked = strtolower($m[2]) === 'x';
                $indent = \strlen($m[1]) >= 2 ? ' roadmap-indent' : '';
                $text = $this->inlineFormat($m[3]);
                $icon = $checked
                    ? '<span class="roadmap-check done">&#10003;</span>'
                    : '<span class="roadmap-check pending">&#9675;</span>';
                $html .= '<li class="roadmap-task' . ($checked ? ' done' : ' pending') . $indent . '">' . $icon . ' ' . $text . '</li>';

                continue;
            }

            // Regular list items
            if (preg_match('/^(\s*)- (.+)$/', $line, $m)) {
                if (!$inList) {
                    $inList = true;
                    $html .= '<ul class="roadmap-list">';
                }
                $indent = \strlen($m[1]) >= 2 ? ' roadmap-indent' : '';
                $html .= '<li class="roadmap-item' . $indent . '">' . $this->inlineFormat($m[2]) . '</li>';

                continue;
            }

            if ($inList && trim($line) === '') {
                $html .= '</ul>';
                $inList = false;
            }

            if (trim($line) === '') {
                continue;
            }

            $html .= '<p class="roadmap-p">' . $this->inlineFormat($line) . '</p>';
        }

        if ($inList) {
            $html .= '</ul>';
        }
        if ($inTable) {
            $html .= '</tbody></table></div>';
        }
        if ($inBlockquote) {
            $html .= $this->renderBlockquote($blockquoteLines);
        }
        if ($inCodeBlock) {
            $html .= '</code></pre>';
        }

        return $html;
    }

    /**
     * @return array{total: int, done: int, sections: list<array{title: string, total: int, done: int}>}
     */
    public function parseStats(string $markdown): array
    {
        $lines = explode("\n", $markdown);
        $totalTasks = 0;
        $doneTasks = 0;
        /** @var list<array{title: string, total: int, done: int}> $sections */
        $sections = [];
        /** @var array{title: string, total: int, done: int}|null $currentSection */
        $currentSection = null;

        foreach ($lines as $line) {
            if (preg_match('/^(#{2,3})\s+(.+)$/', $line, $m)) {
                if ($currentSection !== null) {
                    $sections[] = $currentSection;
                }
                $currentSection = [
                    'title' => strip_tags($this->inlineFormat($m[2])),
                    'total' => 0,
                    'done' => 0,
                ];
            }

            if (preg_match('/^(\s*)- \[([ xX])\]/', $line, $m)) {
                ++$totalTasks;
                if ($currentSection !== null) {
                    ++$currentSection['total'];
                }
                if (strtolower($m[2]) === 'x') {
                    ++$doneTasks;
                    if ($currentSection !== null) {
                        ++$currentSection['done'];
                    }
                }
            }
        }

        if ($currentSection !== null) {
            $sections[] = $currentSection;
        }

        $sections = array_values(array_filter($sections, static fn (array $s): bool => $s['total'] > 0));

        return [
            'total' => $totalTasks,
            'done' => $doneTasks,
            'sections' => $sections,
        ];
    }

    private function inlineFormat(string $text): string
    {
        $text = (string) preg_replace('/`([^`]+)`/', '<code class="roadmap-inline-code">$1</code>', $text);
        $text = (string) preg_replace('/\*\*([^*]+)\*\*/', '<strong>$1</strong>', $text);
        $text = (string) preg_replace('/(?<!\*)\*([^*]+)\*(?!\*)/', '<em>$1</em>', $text);
        $text = (string) preg_replace('/\[([^\]]+)\]\(([^)]+)\)/', '<a href="$2" class="roadmap-link">$1</a>', $text);
        $text = (string) preg_replace('/~~([^~]+)~~/', '<del>$1</del>', $text);
        $text = str_replace('✅', '<span class="roadmap-emoji-done">✅</span>', $text);

        return $text;
    }

    /**
     * @param list<string> $lines
     */
    private function renderBlockquote(array $lines): string
    {
        $content = implode(' ', array_map('trim', $lines));
        $content = $this->inlineFormat($content);

        return '<blockquote class="roadmap-blockquote">' . $content . '</blockquote>';
    }
}
