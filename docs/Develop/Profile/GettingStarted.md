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

# Creating a Profile
Profiles are YAML files with a file extension of `.profile.yml`. These can be placed anywhere but recommended to store in a directory called `Profile`.

# Fields
## title (required)
The title field gives semantic meaning to the collection of policies.

```yaml
title: My custom audit
```

## policies (required)
A list of policies that make up the profile.

```yaml
policies:
  Drupal-7:NoDuplicateModules: {}
  Drupal-7:OverlayModuleDisabled: {}
  Drupal-7:BlackListPermissions: {}
  Drupal-7:PhpModuleDisabled: {}
```

Policy definitions can contain profile specific overrides for parameters passed
to the policy as well as the severity of the policy in context to the profile.

```yaml
policies:
  Database:Size:
    parameters:
      max_size: 900
    severity: critical
```

**Note:** This is a change from the 2.0.x profile format. Older profiles that
provided default parameters will error.

## include
The include directive allows profiles to be build on top of collections or other
profiles. Each include name should be the machine name of another available profile.

```yaml
include:
  - cloud
  - d8
```

## excluded_policies
This directive allows a profile to exclude policies that were implicitly included
in an included profile defined in the `include` directive.

```yaml
excluded_policies:
  - Drupal-7:BlackListPermissions
  - Drupal-7:CSSAggregation
```

## format

The `format` declaration allows a profile to specify options specific to the
export format of the report (console, HTML or JSON). Based on the format,
the options vary.

Right now there are no specific options for `console` or `json` formats. Only HTML.

```
format:
  html:
    template: my-page
    content:
      - heading: My custom section
        body: |
          This is a multiline field that can contain mustache and markdown syntax.
          There are also a variety of variables available to dynamically render
          results.....
```

## format.html.template

The template to use to theme an HTML report. Defaults to `page` which is the option
provided by default. To add your own template you need to register a template
directory and add a template [twig](https://twig.symfony.com/) file.

> drutiny.config.yml:

```yaml
Template:
  - my_templates_dir
```

> < profile >.profile.yml:

```yaml
format:
  html:
    template: my-page
```

The configuration example above will register the `my_templates_dir` directory
(relative to where drutiny.config.yml is placed). When rendering an HTML report,
Drutiny will look inside `my_templates_dir` among other registered template directories
for a template called `my-page.html.twig`. Note that multiple template directories
can be registered.

## format.html.content

Specify the content displayed in an
HTML report and the order that it displays in. By default, Drutiny will load in
the contents from [content.default.yml](https://github.com/drutiny/drutiny/blob/2.3.x/Profiles/content.default.yml).

The content property is an array of sections. Each section specifies a `heading`
and `body`. Each section will roll up into a Table of Contents in the report.

```yaml
format:
  html:
    content:
      - heading: My custom section
        body: |
          This is a multiline field that can contain mustache and markdown syntax.
          There are also a variety of variables available to dynamically render
          results.

          ## Summary
          {{{ summary_table }}}

          {{ #failures }}
            ## Issues
            {{# output_failure }}
              {{{.}}}
            {{/ output_failure }}
          {{ /failures }}

          {{ #warnings }}
            ## Warnings
            {{# output_warning }}
              {{{.}}}
            {{/ output_warning }}
          {{ /warnings }}
```

### Content Variables

These are the variables available to the `format.html.content` template.

Variable | Type | description
--|--|--
`summary_table` | string | A summary table of failures, errors and warnings found from the report.
`appendix_table`| string | A table all results from the audit and data gathering.
`output_failure` | array | An array of rendered failed results
`output_warning` | array | An array of rendered warnings results
`output_error` | array | An array of rendered erroneous results
`output_success` | array | An array of rendered successful results
`output_data` | array | An array of rendered data results
`remediations` | array | An array of recommendations aggregated from failed policies.
`failures` | integer | The number of failed results
`errors` | integer | The number of erred results
`passes` | integer | The number of passed results
`warnings` | integer | The number of failed results
`not_applicable` | integer | The number of results not applicable to tested target.
`notices` | integer | The number of results that provide information/data only.
`title` | string | Profile title
`description` | string | Profile description
`results` | array | An array of result arrays. Its not recommended to use this variable as it requires a lot more complexity for a profile.
