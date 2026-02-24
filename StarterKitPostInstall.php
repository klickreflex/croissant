<?php

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Laravel\Prompts\Prompt;
use Statamic\Support\Str;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Symfony\Component\Yaml\Yaml;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\search;
use function Laravel\Prompts\select;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\text;
use function Laravel\Prompts\warning;

class StarterKitPostInstall
{
    protected string $env = '';

    protected string $system = '';

    protected string $contact = '';

    protected string $sites = '';

    protected bool $interactive = true;

    public function handle($console): void
    {
        $this->applyInteractivity($console);
        $this->loadFiles();
        $this->overwriteEnvWithPresets();
        $this->excludeBuildFolderFromGit();
        $this->excludeUsersFolderFromGit();
        $this->excludeFormsFolderFromGit();
        $this->setupComposerUpdateWorkflow();
        $this->installNodeDependencies();
        $this->writeFiles();
        $this->finish();
    }

    protected function applyInteractivity($console): void
    {
        $this->interactive = ! $console->option('no-interaction');

        Prompt::interactive($this->interactive);
    }

    protected function loadFiles(): void
    {
        $this->env = app('files')->get(base_path('.env.example'));
        $this->system = app('files')->get(base_path('config/statamic/system.php'));
        $this->contact = app('files')->get(base_path('resources/forms/contact.yaml'));
        $this->sites = app('files')->get(base_path('resources/sites.yaml'));
    }

    protected function overwriteEnvWithPresets(): void
    {
        if (! confirm(label: 'Do you want to overwrite your `.env` file with the Croissant presets?', default: true)) {
            return;
        }

        $this->setAppName();
        $this->setLicenseKey();
        $this->setAppUrl();
        $this->setAppKey();
        $this->setLocale();
        $this->setDisplayTimezone();
        $this->setMailFromAddress();
        $this->useDebugbar();
        $this->useImagick();
        $this->setLocalMailer();
        $this->writeEnv();

        info('[✓] `.env` file overwritten.');
    }

    protected function setAppName(): void
    {
        $appName = text(
            label: 'What should be your app name?',
            placeholder: 'My Site',
            default: $this->interactive ? '' : 'My Site',
            required: true,
        );

        $appName = preg_replace('/([\'|\"#])/m', '', $appName);

        $this->replaceInEnv('APP_NAME="Croissant"', "APP_NAME=\"{$appName}\"");
    }

    protected function setLicenseKey(): void
    {
        $licenseKey = text(
            label: 'Enter your Statamic license key',
            hint: 'Leave empty to skip',
            default: '',
            required: false,
        );

        $this->replaceInEnv('STATAMIC_LICENSE_KEY=', "STATAMIC_LICENSE_KEY=\"{$licenseKey}\"");
    }

    protected function setAppUrl(): void
    {
        $appUrl = env('APP_URL');

        $this->replaceInEnv('APP_URL=', "APP_URL=\"{$appUrl}\"");
    }

    protected function setAppKey(): void
    {
        $appKey = env('APP_KEY');

        $this->replaceInEnv('APP_KEY=', "APP_KEY=\"{$appKey}\"");
    }

    protected function setLocale(): void
    {
        $locale = text(
            label: 'What should be the default site locale?',
            placeholder: 'en_US',
            default: 'en_US',
            required: true,
        );

        $this->replaceInSites('locale: en_US', "locale: $locale");
    }

    protected function setDisplayTimezone(): void
    {
        if (! $this->interactive || DIRECTORY_SEPARATOR === '\\') {
            return;
        }

        $newDisplayTimezone = search(
            label: 'What timezone should your app be displayed in?',
            options: function (string $value) {
                if (! $value) {
                    return timezone_identifiers_list(DateTimeZone::ALL, null);
                }

                return collect(timezone_identifiers_list(DateTimeZone::ALL, null))
                    ->filter(fn (string $item) => Str::contains($item, $value, true))
                    ->values()
                    ->all();
            },
            placeholder: 'UTC',
            required: true,
        );

        $currentDisplayTimezone = config('statamic.system.display_timezone');

        $this->replaceInSystem("display_timezone=\"$currentDisplayTimezone\"", "display_timezone=\"$newDisplayTimezone\"");
    }

    protected function setMailFromAddress(): void
    {
        $email = text(
            label: 'What email should be the mail from address?',
            placeholder: 'hello@example.com',
            default: 'hello@example.com',
        );

        $this->replaceInEnv('MAIL_FROM_ADDRESS="hello@example.com"', "MAIL_FROM_ADDRESS=\"{$email}\"");
        $this->replaceInContact('to: info@site.com', "to: {$email}");
        $this->replaceInContact('reply_to: info@site.com', "reply_to: {$email}");
    }

    protected function useDebugbar(): void
    {
        if (confirm(label: 'Do you want to use the debugbar?', default: false)) {
            return;
        }

        $this->replaceInEnv('DEBUGBAR_ENABLED=true', 'DEBUGBAR_ENABLED=false');
    }

    protected function useImagick(): void
    {
        if (! confirm(label: 'Do you want to use Imagick as an image processor instead of GD?', default: true)) {
            return;
        }

        $this->replaceInEnv('#IMAGE_MANIPULATION_DRIVER=imagick', 'IMAGE_MANIPULATION_DRIVER=imagick');
    }

    protected function setLocalMailer(): void
    {
        $localMailer = select(
            label: 'Which local mailer do you use?',
            options: [
                'helo' => 'Helo',
                'herd' => 'Herd Pro',
                'log' => 'Log',
                'mailpit' => 'Mailpit',
                'mailtrap' => 'Mailtrap',
            ],
            default: 'herd',
            scroll: 10,
        );

        if ($localMailer === 'mailpit') {
            return;
        }

        if ($localMailer === 'helo' || $localMailer === 'herd') {
            $this->replaceInEnv('MAIL_HOST=localhost', 'MAIL_HOST=127.0.0.1');
            $this->replaceInEnv('MAIL_PORT=1025', 'MAIL_PORT=2525');
            $this->replaceInEnv('MAIL_USERNAME=null', 'MAIL_USERNAME="${APP_NAME}"');
        }

        if ($localMailer === 'log') {
            $this->replaceInEnv('MAIL_MAILER=smtp', 'MAIL_MAILER=log');
        }
    }

    protected function writeEnv(): void
    {
        app('files')->put(base_path('.env'), $this->env);
    }

    protected function excludeBuildFolderFromGit(): void
    {
        if (! confirm(label: 'Do you want to exclude the `public/_build` folder from git?', default: true)) {
            return;
        }

        $this->appendToGitignore('/public/_build/');
    }

    protected function excludeUsersFolderFromGit(): void
    {
        if (! confirm(label: 'Do you want to exclude the `users` folder from git?', default: false)) {
            return;
        }

        $this->appendToGitignore('/users');
    }

    protected function excludeFormsFolderFromGit(): void
    {
        if (! confirm(label: 'Do you want to exclude the `storage/forms` folder from git?', default: false)) {
            return;
        }

        $this->appendToGitignore('/storage/forms');
    }

    protected function setupComposerUpdateWorkflow(): void
    {
        if (! confirm(label: 'Do you want to add a GitHub workflow that creates PR\'s with Composer updates?', default: true)) {
            return;
        }

        $cron = select(
            label: 'How often do you want this workflow to automatically run?',
            options: [
                '0 2 * * 1' => 'Every week',
                '0 2 1 * *' => 'Every month',
                '0 2 1 */3 *' => 'Every three months',
                false => 'Never, I\'ll trigger it manually',
            ],
            default: '0 2 1 */3 *',
        );

        $cron
            ? $on = [
                'schedule' => [
                    0 => [
                        'cron' => "$cron",
                    ],
                ],
                'workflow_dispatch' => null,
            ]
            : $on = [
                'workflow_dispatch' => null,
            ];

        $workflow = [
            'name' => 'Composer Update',
            'on' => $on,
            'jobs' => [
                'composer_update_job' => [
                    'runs-on' => 'ubuntu-latest',
                    'name' => 'composer update',
                    'steps' => [
                        0 => [
                            'name' => 'Checkout',
                            'uses' => 'actions/checkout@v3',
                        ],
                        1 => [
                            'name' => 'composer update action',
                            'uses' => 'kawax/composer-update-action@master',
                            'env' => [
                                'GITHUB_TOKEN' => '${{ secrets.GITHUB_TOKEN }}',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $disk = Storage::build([
            'driver' => 'local',
            'root' => base_path(),
        ]);

        $disk->makeDirectory('.github/workflows');
        $disk->put('.github/workflows/composer_update.yaml', Yaml::dump($workflow, 99, 2));

        info('[✓] GitHub workflow created.');
    }

    protected function installNodeDependencies(): void
    {
        if (! confirm(label: 'Do you want to install npm dependencies and generate the design token theme?', default: true)) {
            return;
        }

        $this->run(
            command: 'npm i',
            processingMessage: 'Installing npm dependencies...',
            successMessage: 'npm dependencies installed.',
        );

        $this->run(
            command: 'npm run theme',
            processingMessage: 'Generating design token theme...',
            successMessage: 'Design token theme generated.',
        );
    }

    protected function writeFiles(): void
    {
        app('files')->put(base_path('config/statamic/system.php'), $this->system);
        app('files')->put(base_path('resources/forms/contact.yaml'), $this->contact);
        app('files')->put(base_path('resources/sites.yaml'), $this->sites);
    }

    protected function finish(): void
    {
        info('[✓] Croissant is installed. Enjoy!');
        info("Run `npm run dev` to start the Vite dev server. Design token changes are picked up automatically.");
    }

    protected function run(string $command, string $processingMessage = '', string $successMessage = '', ?string $errorMessage = null, int $timeout = 120): bool
    {
        $process = new Process(explode(' ', $command));
        $process->setTimeout($timeout);

        try {
            spin(fn () => $process->mustRun(), $processingMessage);

            if ($successMessage) {
                info("[✓] $successMessage");
            }

            return true;
        } catch (ProcessFailedException $exception) {
            error($errorMessage ?? $exception->getMessage());

            return false;
        }
    }

    protected function replaceInSites(string $search, string $replace): void
    {
        $this->sites = str_replace($search, $replace, $this->sites);
    }

    protected function replaceInContact(string $search, string $replace): void
    {
        $this->contact = str_replace($search, $replace, $this->contact);
    }

    protected function replaceInEnv(string $search, string $replace): void
    {
        $this->env = str_replace($search, $replace, $this->env);
    }

    protected function replaceInSystem(string $search, string $replace): void
    {
        $this->system = str_replace($search, $replace, $this->system);
    }

    protected function appendToGitignore(string $toIgnore): void
    {
        app('files')->append(base_path('.gitignore'), "\n{$toIgnore}");
    }
}
