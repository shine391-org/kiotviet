# Session Log - 2025-11-29 - ERP-005 Advanced Pricing

## Work
- Added advanced pricing schema (customer/project price lists, pricing_rules, price_history) and synced golden migration for testing.
- Implemented models, validators, repositories, and services for pricing rules and price lists; PricingService now prioritises customer/project lists then rules with history logging.
- Added thin API controllers/routes for pricing rules and price preview; wired OrderService preview/create to use PricingService reasons.

## Files
- Migrations: backend-ci/app/Database/Migrations/2025-11-29-001005_CreateAdvancedPricingTables.php; golden update in backend-ci/app/Database/Migrations/2025-11-27-000999_TestSchemaSetup.php
- Code: backend-ci/app/Services/Pricing/*, backend-ci/app/Repositories/Pricing/*, backend-ci/app/Repositories/PriceLists/* (customer/project), backend-ci/app/Controllers/Api/PricingRulesController.php, PricingController.php, backend-ci/app/Services/Orders/OrderService.php, backend-ci/app/Config/Services.php, Routes.php
- Tests: backend-ci/tests/Services/PricingRuleServiceTest.php, PricingServiceTest.php, Integration/Api/PricingApiTest.php

## Tests
- docker exec meomeo2-api-1 vendor/bin/phpunit tests/Services/PricingRuleServiceTest.php
- docker exec meomeo2-api-1 vendor/bin/phpunit tests/Services/PricingServiceTest.php
- docker exec meomeo2-api-1 vendor/bin/phpunit -c phpunit.integration.xml tests/Integration/Api/PricingApiTest.php

## Notes
- Pricing rule evaluation skips zero fixed-price rows, allowing percentage discounts to apply; price history logged when rule changes price.
