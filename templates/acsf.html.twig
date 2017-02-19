<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php print $profile->getTitle(); ?> Report</title>

  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" integrity="sha384-1q8mTJOASx8j1Au+a5WDVnPi2lkFfwwEAa8hDDdjZlpLegxhjVME1fgjWPGmkzs7" crossorigin="anonymous">
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap-theme.min.css" integrity="sha384-fLW2N01lMqjakBkx3l/M9EahuwpSfeNvV63J5ezn3uZzapT0u7EYsXMjQV+0En5r" crossorigin="anonymous">
  <style>
    body {
      padding-top: 50px;
      padding-bottom: 20px;
    }

    .page-header {
      border-bottom: none;
    }

    @media print {
      .display-filters {
        display: none !important;
      }

      .table .danger td,
      .table td.danger,
      .table .danger th {
        background-color: #f2dede !important;
      }
      .table .warning td,
      .table td.warning,
      .table .warning th {
        background-color: #fcf8e3 !important;
      }
      .table .success td,
      .table td.success,
      .table .success th {
        background-color: #dff0d8 !important;
      }
    }
  </style>
</head>
<body>
<nav class="navbar navbar-inverse navbar-fixed-top" role="navigation">
  <div class="container">
    <div class="navbar-header">
      <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
        <span class="sr-only">Toggle navigation</span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
      </button>
      <a class="navbar-brand" href="#"><?php print $profile->getTitle(); ?> report</a>
    </div>
    <div id="navbar" class="navbar-collapse collapse">

    </div><!--/.navbar-collapse -->
  </div>
</nav>

<!-- Main jumbotron for a primary marketing message or call to action -->
<div class="jumbotron">
  <div class="container">
    <h1><?php print $profile->getTitle(); ?></h1>
    <p>Report run across <?php print count($unique_sites); ?> sites<br/>
      <?php print date('Y-m-d h:i a (T)', time()); ?>
    </p>
  </div>
</div>

<div class="container">
  <!-- Start Filters -->
  <div class="row display-filters">
    <div class="col-sm-12">
      <a href="#" id="show-filters" class="btn btn-default"><span class="glyphicon glyphicon-filter" aria-hidden="true"></span> <strong>Show filters</strong></a>
      <fieldset class="display-filter-toggle" style="display: none;">
        <legend><h2>Filters <small>(or)</small></h2></legend>
        <div class="row">
          <div class="col-sm-4">
            <span class="page-header"><small>Domain:</small></span>
            <select id="filter-filter-domains" value="na">
              <?php
                $filter_titles = array();
              ?>
              <option name="na"></option>
              <?php foreach($unique_sites as $id => $site) : ?>
                <option name="<?php print $id ?>" style="font-size:.75em"><?php print str_replace('.', '-', $site['domain']); ?></option>
                <?php
                if (isset($site['results']) && !empty($site['results'])) {
                  $classes = array();
                  $domain = str_replace('.', '-', $site['domain']);
                  $classes[] = 'filter-filter-domains-' . $domain;
                  foreach ($site['results'] as $index => $result) {
                    $outcome = "Failed";
                    if ($result->getStatus() <= 0) {
                      $outcome = "Success";
                    }
                    else if ($result->getStatus() == 1) {
                      $outcome = "Warning";
                    }

                    $class = $result->getTitle();

                    if (!isset($filter_titles[$class]) || !in_array($outcome, $filter_titles[$class])) {
                      $filter_titles[$class][] = $outcome;
                    }

                    $class = str_replace(' ', '-', $class);
                    $class = strtolower($class);
                    $classes[] = strtolower('filter-filter-' . $class . '-' . $outcome);
                  }
                  $unique_sites[$id]['classes'] = $classes;
                }
                ?>
              <?php endforeach; ?>
            </select>
          </div>
          <?php foreach($filter_titles as $key => $value) : ?>
            <div class="col-sm-4">
              <span class="page-header"><small><?php print $key ?>:</small></span>
              <select id="filter-filter-<?php print str_replace(' ', '-', $key); ?>" value="na">
                <option name="na"></option>
                <?php foreach($value as $outcome) : ?>
                  <option name="<?php print $outcome ?>" value="<?php print strtolower($outcome); ?>" style="font-size:.75em"><?php print $outcome; ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          <?php endforeach; ?>
          <div class="col-sm-4">
            <button class="btn btn-primary" id="filter-submit">Filter</button> &nbsp; <button class="btn btn-secondary" id="filter-clear">Clear</button>
          </div>
        </div>
      </fieldset>
    </div>
  </div>
  <!-- End Filters -->
  <!-- Example row of columns -->
  <div class="row">

    <div class="col-sm-12">
      <h2>Sites</h2>

      <table class="table table-bordered">
        <thead>
        <tr>
          <th>Domain</th>
          <th>Site ID</th>
          <th>Check</th>
          <th>Result</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach($unique_sites as $id => $site) : ?>
          <?php if (isset($site['results']) && !empty($site['results'])) : ?>
            <?php foreach($site['results'] as $index => $result) : ?>
              <?php
              $class = "danger";
              if ($result->getStatus() <= 0) {
                $class = "success";
              }
              else if ($result->getStatus() == 1) {
                $class = "warning";
              }
              ?>
              <tr id="domain-<?php print $id ?>" class="filterable <?php print implode(' ', $site['classes']); ?>">
                <?php if ($index == 0) : ?>
                  <th rowspan="<?php print count($site['results']); ?>"><?php print $site['domain']; ?></th>
                  <th rowspan="<?php print count($site['results']); ?>"><?php print $id; ?></th>
                <?php endif; ?>
                <td class="<?php print $class; ?>"><?php print $result->getTitle(); ?></td>
                <td class="<?php print $class; ?>"><?php print $result; ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        <?php endforeach; ?>
        </tbody>
      </table>

    </div>

  </div>

  <hr>

  <footer>
    <p>&copy; Drutiny <?php print date("Y"); ?></p>
  </footer>
</div> <!-- /container -->

<!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
<!-- Latest compiled and minified JavaScript -->
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js" integrity="sha384-0mSbJDEHialfmuBBQP6A4Qrprq5OVfW37PRR3j5ELqxss1yVqOtnepnHVP9aJ7xS" crossorigin="anonymous"></script>

<script type="text/javascript">
  $(document).ready(function() {
    $("#show-filters").click(function() {
      $(".display-filter-toggle").toggle();
      if ($("#show-filters strong").text() == "Hide filters") {
        $("#show-filters strong").text("Show filters");
      }
      else {
        $("#show-filters strong").text("Hide filters");
      }
    });

    $("#filter-clear").click(function() {
      $("[id^='filter-filter-']").each(function () {
        $(this).val("na")
      });
      $(".filterable").each(function() {
        var target = $(this);
        target.show();
      });
    });

    $("#filter-submit").click(function() {
      var filters = "";
      $("[id^='filter-filter-']").each(function() {
        var filter = $(this).val();
        if(filter != '') {
          filters = filters + ' .' + $(this).attr('id') + '-' + filter + ',';
        }
      });
      if (filters != "") {
        filters = filters.toLowerCase().slice(0, -1);
      }
      else {
        filters = ".filterable";
      }

      $(".filterable").each(function() {
        var target = $(this);
        if (target.is(filters)) {
          target.show();
        }
        else {
          target.hide();
        }
      });
    });
  });
</script>

</body>
</html>
