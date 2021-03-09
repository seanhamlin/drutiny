# Charts (HTML only)

Charts in Drutiny are an HTML format feature that allows rendered tabular data
in a policy to be visualized in a chart inside of the HTML generated report.

Under the hood, Drutiny uses [chart.js](https://www.chartjs.org/) to render charts.

A chart is defined inside of a [Policy](policy.md) as metadata and rendered
inside of either the success, failure, warning or remediation messages also
provided by the policy.

```yaml
chart:
  requests:
    type: doughnut
    labels: tr td:first-child
    hide-table: false
    title: Request Distribution by Domain
    height: 300
    width: 400
    legend: left
    colors:
      - rgba(46, 204, 113,1.0)
      - rgba(192, 57, 43,1.0)
      - rgba(230, 126, 34,1.0)
      - rgba(241, 196, 15,1.0)
      - rgba(52, 73, 94,1.0)
    series:
      - tr td:nth-child(4)
success: |
  Here is a doughnut chart:
  {{{_chart.requests}}}
```

## Configuration

Any given policy may have a `chart` property defined in its `.policy.yml` file.
The `chart` property contains a arbitrarily keyed set of chart definitions.

```yaml
chart:
  my_chart_1:
    # ....
  my_chart_2:
    # ....
```

Charts use tabular data from the first sibling table in the DOM.

## Chart Properties
`labels` and `series` use css selectors powered by jQuery to obtain the data to
display in the chart.

Property     | Description
------------ | -----------
`type`       | The type of chart to render. Recommend `bar`, `pie` or `doughnut`.
`labels`     | A css selector that returns an array of HTML elements whose text will become labels in a pie chart or x-axis in a bar graph.
`hide-table` | A boolean to determine if the table used to read the tabular data should be hidden. Defaults to false.
`title`      | The title of the graph
`series`     | An array of css selectors that return the HTML elements whose text will become chart data.
`height`     | The height of the graph area set as a CSS style on the `<canvas>` element.
`width`      | The width of the graph area set as a CSS style on the `<canvas>` element.
`x-axis`     | The label for the x-axis.
`y-axis`     | The label for the y-axis.
`legend`     | The position of the legend. Options are: top, bottom, left, right or none (to remove legend).
`colors`     | An array of colors expressed using RGB syntax. E.g. `rgba(52, 73, 94,1.0)`.

## Rendering a Chart
Rendered charts are available as a special `_chart` token to be used in success,
failure, warning or remediation messages provided by the policy.

```yaml
success: |
  Here is the chart:
  {{{_chart.my_chart_1}}}
```

Is important to use triple curly braces here to ensure the variables are not
escaped by the mustache templating language.
