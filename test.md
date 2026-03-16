# GegoK12

#### Version 1.0

## How to Install

1. Pull the Repo from the GitLab
2. Run "composer install"
3. Run "dusk install"
4. Duplicate .env file as .env.dusk.local
5. Add your mysql db details there
6. Run php artisan dusk --filter classname //for single test
7. Run php artisan dusk --filter classname::functionname//for single function
7. Run php artisan dusk //to run all the test
