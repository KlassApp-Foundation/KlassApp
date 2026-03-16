<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Http\Controllers\Admin\Addon;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Exception;
use Log;

class AddonInstallExamController extends Controller
{
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('admin.addon.install.exam');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try
        {
                    $request->validate([
                    'repository_url' => 'required|url',
                    'package_name' => 'required',
                    'git_username' => 'required',
                    'git_token' => 'required',
                ]);

                $repo = $request->repository_url;
                $package = $request->package_name;
                $username = $request->git_username;
                $token = $request->git_token;

                // Inject credentials into repo URL
                $parsed = parse_url($repo);
                $authUrl = "{$parsed['scheme']}://{$username}:{$token}@{$parsed['host']}{$parsed['path']}";

                // Modify composer.json
                $composerPath = base_path('composer.json');
                $composer = json_decode(file_get_contents($composerPath), true);

                $alreadyExists = collect($composer['repositories'] ?? [])->contains(fn($r) => $r['url'] === $repo);
                if (!$alreadyExists) {
                    $composer['repositories'][] = ['type' => 'vcs', 'url' => $authUrl];
                    file_put_contents($composerPath, json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                }

                // Run composer require
                exec("composer require {$package}:dev-main", $output, $status);
                if ($status !== 0) {
                      \Session::put('failmessage','Failed to install package.');
                    return back();
                }

                // Publish assets
                foreach (['exam-routes', 'exam-components', 'exam-views', 'exam-migrations', 'exam-config','examteacherapi-routes'] as $tag) {
                    Artisan::call('vendor:publish', ['--tag' => $tag, '--force' => true]);
                }

                // Append route loader
                $routesPath = base_path('routes/web.php');
                $requireLine = "if (file_exists(base_path('routes/gexam.php'))) {\n    require base_path('routes/gexam.php');\n}";
                if (!str_contains(file_get_contents($routesPath), 'routes/gexam.php')) {
                    file_put_contents($routesPath, "\n" . $requireLine . "\n", FILE_APPEND);
                }


                $requireLine = "if (file_exists(base_path('routes/gexamteacherapi.php'))) {\n    require base_path('routes/gexamteacherapi.php');\n}";
                if (!str_contains(file_get_contents($routesPath), 'routes/gexamteacherapi.php')) {
                    file_put_contents($routesPath, "\n" . $requireLine . "\n", FILE_APPEND);
                }

                // Add JS include to app.js
                $appJsPath = resource_path('js/app.js');
                if (!str_contains(file_get_contents($appJsPath), "require('./gexam')")) {
                    file_put_contents($appJsPath, file_get_contents($appJsPath) . "\nrequire('./gexam');\n");
                }

                // Run npm
                exec('npm install', $npmOut, $npmStatus);
                exec('npm run dev', $devOut, $devStatus);

                // Run migrations
                Artisan::call('migrate', ['--force' => true]);
                \Session::put('successmessage','Module installed successfully!');

                return redirect()->back();
            }
        
        catch(Exception $e)
        {
            //dd($e->getMessage());
            Log::info($e->getMessage());
        }
    }
}