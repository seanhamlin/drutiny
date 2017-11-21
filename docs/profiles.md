# Profiles
Profiles are a collection of policies that aim to audit a target against a
specific context or purpose. Some examples of profile use cases are:
- Production-ready Drupal 8 site
- Organizational policy compliance
- Security or performance audit

Profiles allow you to run a defined set of polices into a report.

```
./vendor/bin/drutiny profile:run <profile_name> <target>
```

Reports can also be rendered into HTML or JSON and saved to file.

```
./vendor/bin/drutiny profile:run <profile_name> <target> --format=html -o <filename>
```

## Creating a Profile
Profiles are YAML files with a file extension of `.profile.yml`. These can be placed anywhere but recommended to store in a directory called `Profile`.

## Fields
### title (required)
The title field gives semantic meaning to the collection of policies.

```yaml
title: My custom audit
```

### policies (required)
A list of policies that make up the profile.

```yaml
policies:
  Drupal-7:NoDuplicateModules: {}
  Drupal-7:OverlayModuleDisabled: {}
  Drupal-7:BlackListPermissions: {}
  Drupal-7:PhpModuleDisabled: {}
```

### include
The include directive allows profiles to be build on top of collections or other
profiles. Each include name should be the machine name of another available profile.

```yaml
include:
  - cloud
  - d8
```
