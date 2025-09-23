{
    "name": "sage-gg/financial-system",
    "description": "Branch of CraneSYSTEM focusing on financials",
    "type": "project",
    "license": "MIT",
    "require": {
        "php": "^8.0",
        "phpmailer/phpmailer": "^6.10"
    },
    "require-dev": {
        "phpunit/phpunit": "^9.5"
    },
    "autoload": {
        "psr-4": {
            "YourNamespace\\": "src/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "YourNamespace\\Tests\\": "tests/"
        }
    },
    "scripts": {
        "test": "phpunit"
    }
}
