1. Install PHPUnit before running tests with `composer update`.
2. Run all tests with `./phpunit`.
3. Clean up PHPUnit + dependencies with `composer update --no-dev'.
4. Commit your work.

MCP Adapter integration tests

Download the official MCP Adapter release from https://wordpress.org/plugins/mcp-adapter/ and extract it outside this repository. Run the integration tests against the WordPress test database with Local's environment:

    direnv exec /Users/jeroen/Sites/jeero-plugin/app/public env WP_TESTS_DIR=/tmp/wordpress-tests-lib JEERO_MCP_ADAPTER_PLUGIN=/absolute/path/to/mcp-adapter/mcp-adapter.php php /Users/jeroen/bin/phpunit.phar --configuration tests/configuration/Jeero.xml --group mcp-adapter

The tests send JSON-RPC messages through the adapter's HTTP transport route using WordPress's REST test client. They cover initialization, sessions, tools/list, discovery, schemas, all eleven abilities, the MCP setting and read permissions. Mother responses are mocked; no external subscriptions are changed. Without JEERO_MCP_ADAPTER_PLUGIN, these optional tests are skipped.



Jeero MCP Settings
------------------
The admin suite covers disabled MCP, enabled MCP with the official adapter active, a missing/inactive adapter, and endpoint generation in a WordPress subdirectory with pretty and plain permalinks. Adapter activation is simulated through WordPress options for these rendering tests.
Run `node --test tests/js/mcp-copy.test.cjs` for clipboard success, denial, unavailable clipboard and absent-button states.
