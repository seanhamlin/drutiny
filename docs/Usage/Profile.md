# Profile Usage

Profiles allow you to run a group of policies against a given target and format
the results into a report.

## Discover

The profiles available in Drutiny can be found using the `profile:list` command.

```
drutiny profile:list
```

This should provide you with a table of available profiles including the profile
title, name, and source.

## Profile information

Additonal information about a specific profile can be viewed using the
`profile:info <profile:name>` command. Specifically, this command informs you of the
policies in use by this profile.

```
drutiny profile:info <profile:name>
```

For more information on individual policies, use the `policy:info` command.
See [policy usage](Policy.md) for more information.

## Auditing a target againt a profile

Using the `profile:run` command you can assess a target against the list of
policies in the profile.

```
drutiny profile:run <profile:name> <target>
```

The target argument should be an identifier from the `target:list <source>`
command. See [Target Usage](Target.md) for more information.

## Formatting profile reports

Drutiny supports a number of formats. The default formats include terminal
(default), json, csv, markdown and html. When using `profile:run`you can specify
one or more formats to render the report out to. Use the `--format` or `-f`
options to set a format.

```
drutiny profile:run --format=html -f csv <profile:name> <target>
```

To place a report in a predefined folder, speific the folder name with the `-o`
 or `--report-dir` option.

```
drutiny profile:run -f json -o reports/ <profile:name> <target>
```

## Setting reporting periods.

Some policies report on data examined across a time period. For these policies
and underlying audit classes, they require a reporting period range. By default,
Drutiny sets the reporting period as the last 24 hours. For larger reporting
periods you can use the `--reporting-period-start` and `--reporting-period-end`
options. Alternatively, you can provide the entire period using
`--reporting-period`.

```
# Report on the last 7 days
drutiny profile:run <profile:name> <target> -f html --reporting-period-start='-7 days'

# Reporting on an absolute time period.
drutiny profile:run <profile:name> <target> -f html --reporting-period-start='2021-01-01 00:00:00' --reporting-period-end='2021-02-01 00:00:00'

# Using the reporting period syntax.
drutiny profile:run <profile:name> <target> -f html --reporting-period='01/01/2021 00:00:00 TO 01/02/2021 00:00:00'
```

## Including and excluding policies from a profile

If a profile contains a policy to which to omit from the report, or is missing
a policy you'd like to include in the report, you can use the the include and
exclude policy options.

```
drutiny profile:run --include-policy=<policy:name> --exclude-policy=<policy:name> <profile:name> <target> -f csv
```
