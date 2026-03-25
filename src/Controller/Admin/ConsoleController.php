<?php

namespace App\Controller\Admin;

use App\Service\AdminLogger;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/console', name: 'admin_console_')]
#[IsGranted('ROLE_ADMIN')]
class ConsoleController extends AbstractController
{
    /** Commands that must never be run from the web UI. */
    private const BLOCKED_COMMANDS = [
        'server:start', 'server:stop', 'server:run',
        'secrets:decrypt-to-local', 'secrets:encrypt-from-local',
        'secrets:generate-keys', 'secrets:set', 'secrets:remove',
    ];

    /** Only expose commands in these namespaces. */
    private const ALLOWED_NAMESPACES = [
        'app',
        'cache',
        'doctrine',
        'tailwind',
        'asset-map',
        'importmap',
        'debug',
        'make',
    ];

    public function __construct(
        private readonly KernelInterface $kernel,
        private readonly AdminLogger $adminLogger,
    ) {
    }

    #[Route('', name: 'index')]
    public function index(): Response
    {
        $commands = $this->getAvailableCommands();

        // Group by namespace
        $grouped = [];
        foreach ($commands as $cmd) {
            $ns = explode(':', $cmd['name'])[0];
            $grouped[$ns][] = $cmd;
        }
        ksort($grouped);

        return $this->render('admin/console/index.html.twig', [
            'grouped' => $grouped,
        ]);
    }

    #[Route('/detail/{commandName}', name: 'detail', requirements: ['commandName' => '.+'], methods: ['GET'])]
    public function detail(string $commandName): JsonResponse
    {
        $app = $this->createConsoleApp();
        if (!$app->has($commandName)) {
            return $this->json(['error' => 'Commande introuvable'], 404);
        }

        $command = $app->find($commandName);
        $definition = $command->getDefinition();

        $arguments = [];
        foreach ($definition->getArguments() as $arg) {
            $arguments[] = [
                'name' => $arg->getName(),
                'description' => $arg->getDescription(),
                'required' => $arg->isRequired(),
                'isArray' => $arg->isArray(),
                'default' => $arg->getDefault(),
            ];
        }

        $options = [];
        foreach ($definition->getOptions() as $opt) {
            // Skip global Symfony options
            if (in_array($opt->getName(), ['help', 'quiet', 'verbose', 'version', 'ansi', 'no-ansi', 'no-interaction', 'env', 'no-debug', 'profile'], true)) {
                continue;
            }
            $options[] = [
                'name' => $opt->getName(),
                'shortcut' => $opt->getShortcut(),
                'description' => $opt->getDescription(),
                'acceptValue' => $opt->acceptValue(),
                'isValueRequired' => $opt->isValueRequired(),
                'isArray' => $opt->isArray(),
                'default' => $opt->getDefault(),
                'isNegatable' => $opt->isNegatable(),
            ];
        }

        return $this->json([
            'name' => $command->getName(),
            'description' => $command->getDescription(),
            'help' => $command->getProcessedHelp(),
            'arguments' => $arguments,
            'options' => $options,
        ]);
    }

    #[Route('/run', name: 'run', methods: ['POST'])]
    public function run(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $commandName = $data['command'] ?? '';
        $args = $data['arguments'] ?? [];
        $opts = $data['options'] ?? [];

        if (empty($commandName)) {
            return $this->json(['error' => 'Nom de commande manquant'], 400);
        }

        if (in_array($commandName, self::BLOCKED_COMMANDS, true)) {
            return $this->json(['error' => 'Cette commande est bloquee pour des raisons de securite'], 403);
        }

        // Verify command is in allowed namespaces
        $ns = explode(':', $commandName)[0];
        if (!in_array($ns, self::ALLOWED_NAMESPACES, true)) {
            return $this->json(['error' => 'Namespace non autorise: ' . $ns], 403);
        }

        $app = $this->createConsoleApp();
        if (!$app->has($commandName)) {
            return $this->json(['error' => 'Commande introuvable: ' . $commandName], 404);
        }

        // Build input array
        $inputArray = ['command' => $commandName];
        $command = $app->find($commandName);
        $definition = $command->getDefinition();

        // Map arguments
        foreach ($args as $name => $value) {
            if ($definition->hasArgument($name) && $value !== '' && $value !== null) {
                $inputArray[$name] = $value;
            }
        }

        // Map options
        foreach ($opts as $name => $value) {
            if (!$definition->hasOption($name)) {
                continue;
            }
            $optDef = $definition->getOption($name);
            if ($optDef->acceptValue()) {
                if ($value !== '' && $value !== null) {
                    $inputArray['--' . $name] = $value;
                }
            } else {
                // Boolean flag
                if ($value === true || $value === 'true' || $value === '1') {
                    $inputArray['--' . $name] = true;
                }
            }
        }

        $input = new ArrayInput($inputArray);
        $input->setInteractive(false);
        $output = new BufferedOutput();

        try {
            $exitCode = $app->doRun($input, $output);
        } catch (\Throwable $e) {
            $this->adminLogger->log('execute', 'Console', null, sprintf('ERREUR %s: %s', $commandName, $e->getMessage()));

            return $this->json([
                'exitCode' => 1,
                'output' => $e->getMessage(),
            ]);
        }

        $this->adminLogger->log(
            'execute',
            'Console',
            null,
            sprintf('Commande "%s" executee (exit %d)', $commandName, $exitCode)
        );

        return $this->json([
            'exitCode' => $exitCode,
            'output' => $output->fetch(),
        ]);
    }

    private function createConsoleApp(): Application
    {
        $app = new Application($this->kernel);
        $app->setAutoExit(false);

        return $app;
    }

    /**
     * @return array<int, array{name: string, description: string}>
     */
    private function getAvailableCommands(): array
    {
        $app = $this->createConsoleApp();
        $commands = [];

        foreach ($app->all() as $name => $command) {
            if ($command->isHidden()) {
                continue;
            }

            $ns = explode(':', $name)[0];
            if (!in_array($ns, self::ALLOWED_NAMESPACES, true)) {
                continue;
            }

            if (in_array($name, self::BLOCKED_COMMANDS, true)) {
                continue;
            }

            $commands[] = [
                'name' => $name,
                'description' => $command->getDescription() ?: '',
            ];
        }

        usort($commands, fn ($a, $b) => strcmp($a['name'], $b['name']));

        return $commands;
    }
}
