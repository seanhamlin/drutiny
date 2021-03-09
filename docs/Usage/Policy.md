# Policy Usage

Policy objects define how an audit in Drutiny should assess a given target.
Drutiny has a library of policies to choose from provided by a collection
of pluggable sources.

## Discover

To find what policies are available in your instance of Drutiny, us the
`policy:list`command.

```
drutiny policy:list
```

This should provide you with a table of available policies including the policy
title, name, source and profile utilisation.

## Policy information

The title and name of the policy should be indicative of what the policy
audits for and assesses. To find out more information, use the `policy:info`
command to see the description and parameters used in the audit.

```
drutiny policy:info <policy:name>
```

The output from this command should also give you an example of how to audit
a target against this policy.

## Auditing a target against a policy.

Using the `policy:audit` command you can assess a single policy against a single
target.

```
drutiny policy:audit <policy:name> <target>
```

Use the name column from the `policy:list`command as the `<policy:name>`
argument. The target argument should be an identifier from the `target:list <source>`
command. See [Target Usage](Target.md) for more information.
