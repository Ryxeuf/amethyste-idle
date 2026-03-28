<?php

namespace App\Service\Monitoring;

use Psr\Cache\CacheItemPoolInterface;

class MetricsCollector
{
    private const CACHE_KEY = 'app_metrics';
    private const HISTOGRAM_BUCKETS = [0.01, 0.025, 0.05, 0.1, 0.25, 0.5, 1.0, 2.0, 5.0, 10.0];

    public function __construct(
        private readonly CacheItemPoolInterface $cache,
    ) {
    }

    public function incrementCounter(string $name, float $value = 1.0, string $labels = ''): void
    {
        $metrics = $this->loadMetrics();
        $key = "counter:{$name}:{$labels}";
        $metrics[$key] = ($metrics[$key] ?? 0.0) + $value;
        $this->saveMetrics($metrics);
    }

    public function observeHistogram(string $name, float $value, string $labels = ''): void
    {
        $metrics = $this->loadMetrics();

        $sumKey = "histogram_sum:{$name}:{$labels}";
        $countKey = "histogram_count:{$name}:{$labels}";
        $metrics[$sumKey] = ($metrics[$sumKey] ?? 0.0) + $value;
        $metrics[$countKey] = ($metrics[$countKey] ?? 0.0) + 1.0;

        foreach (self::HISTOGRAM_BUCKETS as $bucket) {
            $bucketKey = "histogram_bucket:{$name}:{$labels}:le={$bucket}";
            if ($value <= $bucket) {
                $metrics[$bucketKey] = ($metrics[$bucketKey] ?? 0.0) + 1.0;
            }
        }

        $infKey = "histogram_bucket:{$name}:{$labels}:le=+Inf";
        $metrics[$infKey] = ($metrics[$infKey] ?? 0.0) + 1.0;

        $this->saveMetrics($metrics);
    }

    public function setGauge(string $name, float $value, string $labels = ''): void
    {
        $metrics = $this->loadMetrics();
        $key = "gauge:{$name}:{$labels}";
        $metrics[$key] = $value;
        $this->saveMetrics($metrics);
    }

    /**
     * @return array<string, float>
     */
    public function getAll(): array
    {
        return $this->loadMetrics();
    }

    /**
     * Renders all metrics in Prometheus exposition format.
     */
    public function renderPrometheus(): string
    {
        $metrics = $this->loadMetrics();
        $lines = [];
        $declared = [];

        ksort($metrics);

        foreach ($metrics as $key => $value) {
            $parts = explode(':', $key, 4);
            $type = $parts[0];
            $name = $parts[1] ?? '';
            $labels = $parts[2] ?? '';

            $metricName = "amethyste_{$name}";

            if ($type === 'counter') {
                if (!isset($declared[$metricName])) {
                    $lines[] = "# HELP {$metricName} Counter {$name}";
                    $lines[] = "# TYPE {$metricName} counter";
                    $declared[$metricName] = true;
                }
                $labelStr = $labels ? '{' . $labels . '}' : '';
                $lines[] = "{$metricName}{$labelStr} {$value}";
            } elseif ($type === 'gauge') {
                if (!isset($declared[$metricName])) {
                    $lines[] = "# HELP {$metricName} Gauge {$name}";
                    $lines[] = "# TYPE {$metricName} gauge";
                    $declared[$metricName] = true;
                }
                $labelStr = $labels ? '{' . $labels . '}' : '';
                $lines[] = "{$metricName}{$labelStr} {$value}";
            } elseif ($type === 'histogram_sum') {
                $sumName = "{$metricName}_sum";
                $labelStr = $labels ? '{' . $labels . '}' : '';
                if (!isset($declared[$metricName])) {
                    $lines[] = "# HELP {$metricName} Histogram {$name}";
                    $lines[] = "# TYPE {$metricName} histogram";
                    $declared[$metricName] = true;
                }
                $lines[] = "{$sumName}{$labelStr} {$value}";
            } elseif ($type === 'histogram_count') {
                $countName = "{$metricName}_count";
                $labelStr = $labels ? '{' . $labels . '}' : '';
                $lines[] = "{$countName}{$labelStr} {$value}";
            } elseif ($type === 'histogram_bucket') {
                $bucketName = "{$metricName}_bucket";
                $le = $parts[3] ?? '+Inf';
                $leVal = str_replace('le=', '', $le);
                $extraLabels = $labels ? "{$labels}," : '';
                $lines[] = "{$bucketName}{{$extraLabels}le=\"{$leVal}\"} {$value}";
            }
        }

        return implode("\n", $lines) . "\n";
    }

    /**
     * @return array<string, float>
     */
    private function loadMetrics(): array
    {
        $item = $this->cache->getItem(self::CACHE_KEY);
        if ($item->isHit()) {
            /** @var array<string, float> $data */
            $data = $item->get();

            return $data;
        }

        return [];
    }

    /**
     * @param array<string, float> $metrics
     */
    private function saveMetrics(array $metrics): void
    {
        $item = $this->cache->getItem(self::CACHE_KEY);
        $item->set($metrics);
        $item->expiresAfter(86400);
        $this->cache->save($item);
    }
}
