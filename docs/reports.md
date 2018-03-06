# Working with Reports


## Customize your Report

### Formats

Drutiny comes with 3 types of report formats: CLI, &nbsp;JSON, &nbsp;HTML.<br>
If you do not specify any report format, the CLI format will be used by default.

Example to run a JSON report by using the "-f" param.

```
./vendor/bin/drutiny profile:run d8 @drupalvm.dev -f json
```

or you can use the HTML report with telling Drutiny, where to store the html.

```
./vendor/bin/drutiny profile:run d8 @drupalvm.dev -f html -o ./report1.html
```
