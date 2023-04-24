<?php
use CodeIgniter\Config\DotEnv;

// Load the Config class
$config = new DotEnv();

// Load the environment variables
$config->load();

// Modify an environment variable
putenv('ENVIRONMENT=development');

// Clear the environment variable cache
$config->reset();

// Load the environment variables again
$config->load();

// The modified value of the environment variable should now be loaded
echo getenv('ENVIRONMENT');



?>