{
    "name": "financial-reporting/excel-export",
    "description": "Financial Reporting System with Excel Export",
    "type": "project",
    "require": {
        "php": ">=7.4",
        "phpoffice/phpspreadsheet": "^1.29"
    },
    "autoload": {
        "psr-4": {
            "App\\": "src/"
        }
    },
    "config": {
        "platform": {
            "php": "7.4"
        },
        "optimize-autoloader": true,
        "sort-packages": true
    }
}