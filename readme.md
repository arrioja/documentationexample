# COMPANY NAME DELETED ON PURPOSE #

## Framework Details ##
Laravel Base Platform for Employee Loans, APR Calculator and ACH File Generator

## Development Requirements ##
  
  - Docker & Docker Compose
  - GNU Make

## Installation ##
  1) Add Entry To Your Host File: `127.0.0.1 portal.yesIdeletedthecompanynamefromheretoo.test db.yesIdeletedthecompanynamefromheretoo.test`
  2) Run `make install`
  3) Navigate To `http://portal.yesIdeletedthecompanynamefromheretoo.test`
  
## E-Mails ##
This project uses MailHog to manage all emails sent from the system while developing. This can b reached by visiting `http://portal.yesIdeletedthecompanynamefromheretoo.test:8080/` in your browser.

## Common Make Commands ##
Additional commands exist in the Makefile. These are the most common ones.

  1) `make install` - setup project
  2) `make clean` - remove all make installed dependencies and files
  3) `make clean-install` - Runs the clean command followed by the install command.
  5) `make analyse` - Run all QA and testing
  6) `make test` - Run All Tests
  7) `make test-unit` - Run Unit Testing
  8) `make test-functional` - Run Functional Testing
  9) `make test-acceptance` - Run Acceptance Testing
  10) `make cs-fix` - Run PHP-CS-Fixer
  11) `make phpstan` - Run PHPStan
  12) `make clear-cache` - Clears Laravel Cache
  13) `make migrate` - Runs Laravel Migration
  14) `make migrate-reset` - Clears/ReRuns Migration
  15) `make stop` - Stop Docker Containers - Uses Docker Down
  16) `make start` - Start Docker Containers - Uses Docker Up

  If you are having any trouble installing this, please reach out at pedro.arrioja@yesIdeletedthecompanynamefromheretoo.com
