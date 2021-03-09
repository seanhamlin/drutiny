# Setup

Drutiny can operate out-of-the-box. However some plugins may require setup and
you may have local configuration you might like to make.

## Plugins

Some plugins require configuration or credentials to work effectively. To
set these plugins up, you'll need to run the `plugin:setup <plugin>` command and
provide the required credentials.

Use `plugin:list` to view a list of available plugins and if they've been
installed yet or not. To install the plugin, simply run `plugin:setup` with the
`namespace` value as the `<plugin>` argument.

```
drutiny plugin:setup <plugin:namespace>
```

If a plugin is already setup, you can view the configuration details by using
`plugin:view <plugin:namespace>`.


## Configuration

Drutiny manages configuration using a file called `drutiny.yml`. Any plugins or
projects using Drutiny may provide their own `drutiny.yml` file. You can
override Drutiny's default configuration by creating a `drutiny.yml` file and
overwriting any value.

### Parameters

Configuration      | Default Value                             | Description
------------------ | ----------------------------------------- | -----------
drutiny_config_dir | '%user_home_dir%/.drutiny'                | Where to store drutiny config and credentials.
policy.library.fs  | '%drutiny_config_dir%/policy'             | Where to look for localfs policies
cache.directory    | '%drutiny_config_dir%/cache'              | Where to store cached items
cache.ttl          | 3600                                      | TTL For cached items
config.local       | '%drutiny_config_dir%/config.yml'         | Where config is stored
config.credentials | '%drutiny_config_dir%/.credentials.yml'   | Where credentials are stored
config.old_path    | '%drutiny_config_dir%/.drutiny_creds.yml' | The location for drutiny 2.x credentials.
log.directory      | '%user_home_dir%/.drutiny/logs'           | Where the logs from drutiny are written too.
log.name           | drutiny                                   | The namespace for the logs
log.filepath       | '%log.directory%/%log.name%.log'          | The filepath of for the log file
log.level          | WARNING                                   | The log level to log and above.
log.max_files      | 20                                        | The maximum number of files before they are rotated.
twig.templates     | ['%drutiny_core_dir%/twig', '%drutiny_core_dir%/twig/report'] | Locations hwere to find twig templates.
twig.cache         | '%drutiny_config_dir%/twig'               | Location where to find twig cache.
twig.debug         | true                                      | Turn on twig debugging.
twig.strict        | true                                      | Whether twig is strict or relazed.
async.forks        | 7                                         | Max number of forks to run in parallel
language_default   | en                                        | The default language to operate in.
progress_bar.template | %%message%%\n%%current%%/%%max%% [%%bar%%] %%percent:3s%%%% %%elapsed:6s%% | The template to use for the progress bar.
