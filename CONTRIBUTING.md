# Contributing to Drutiny

In general the Drutiny team will be looking to find ways to help people contribute. Pull requests are definitely appreciated.

## Small changes that will be accepted

* New checks that others can utlise. Ideally checks will have arguments where needed to make this as easy as possible to adapt for other sites.
* Spelling typos, grammar changes etc
* Better comments and code style

## Larger changes that will be accepted

* New integrations with other tools, where they provide significant value
* Any better OO techniques and code organisation
* Any open issues currently in the issue queue
* Anything that involves making the codebase more testible
* Removing technical debt

## Changes that will be (most likely) rejected

* Anything that requires special sauce in order to run
* Vendor specific checks that cannot be re-used


# Tests

There are no tests currently in Drutiny, it is hoped that at some point in the future this could be recitified. Ideally PHP unit or similar could be used, and all external dependencies could be stubbed or mocked. Having TravisCI integration would be ideal, so all PRs could be tested.
