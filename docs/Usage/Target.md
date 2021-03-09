# Target Usage

Targets are objects Drutiny audits policies against. Drutiny supports targets
of multiple types. Drutiny comes with two default targets: NullTarget and
DrushTarget.

Additional targets can be added by plugins.

## Discover

To discover your available targets, run `target:sources` to list the targets
sources and use `target:list <source>` to list targets available from a given
source.

```
drutiny target:sources
```

```
drutiny target:list <source>
```

From the list provided in `target:list <source>` you'll be able to select a
target to audit a policy against with `policy:audit` or run a profile against
with `profile:run`.

## Getting information about the target.

When a target loads, the target source makes available additional metadata
about the target. This information is not particularly useful in general usage
of Drutiny (such as running profiles or policy audits) but knowing what
information is available on a target is very useful when building a policy,
audit or your own target class.

```
drutiny target:info <target>
```
