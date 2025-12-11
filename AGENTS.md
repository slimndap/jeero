## Running PHPUnit

You can test new code changes by running the primary suite of PHPUnit integration tests.

- **Install dev deps first:** from the project root run `composer install` (needed for `yoast/phpunit-polyfills` so bootstrap works).
- **Primary suite:** `php /Users/jeroen/bin/phpunit.phar --configuration tests/configuration/Jeero.xml` (or use the `phpunit` script in the root which chains all configs).
- **MCP phpunit server (use this):** call the `phpunit` MCP tool with `project_root` `/Users/jeroen/Sites/jeero-plugin/app/public/wp-content/plugins/jeero` and `args` array `["--configuration","tests/configuration/Jeero.xml"]` (array, not a single string). Example payload:

  ```json
  {
    "project_root": "/Users/jeroen/Sites/jeero-plugin/app/public/wp-content/plugins/jeero",
    "args": ["--configuration","tests/configuration/Jeero.xml"]
  }
  ```
