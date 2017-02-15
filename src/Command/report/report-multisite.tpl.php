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
    @media print {
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
  <!-- Example row of columns -->
  <div class="row">

    <div class="col-sm-12">
      <h2>Sites</h2>

      <table class="table table-bordered">
        <thead>
          <tr>
            <th>Domain</th>
            <th>Check</th>
            <th>Result</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($unique_sites as $id => $site) : ?>
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
              <tr>
                <?php if ($index == 0) : ?>
                  <th rowspan="<?php print count($site['results']); ?>"><?php print $site['domain']; ?></th>
                <?php endif; ?>
                <td class="<?php print $class; ?>">
                  <?php print $result->getTitle(); ?>
                </td>
                <td class="<?php print $class; ?>">
                  <?php print $result; ?>
                  <?php if ($result->getStatus() > 1) : ?>
                    <p><?php print $result->getDescription(); ?></p>
                    <div class="panel panel-danger">
                      <div class="panel-heading">Remediation</div>
                      <div class="panel-body">
                        <p><?php print $result->getRemediation(); ?></p>
                      </div>
                    </div>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
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

</body>
</html>
