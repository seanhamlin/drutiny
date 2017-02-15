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
      .table .danger th {
        background-color: #f2dede !important;
      }
      .table .warning td,
      .table .warning th {
        background-color: #fcf8e3 !important;
      }
      .table .success td,
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
    <p>Report run across <?php print $site['domain']; ?><br/>
      <?php print date('Y-m-d h:i a (T)', time()); ?>
    </p>
  </div>
</div>

<div class="container">
  <!-- Example row of columns -->
  <div class="row">

    <div class="col-sm-12">
      <h2><?php print $site['domain']; ?></h2>

      <table class="table">
        <thead>
          <tr>
            <th>Check</th>
            <th>Result</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($site['results'] as $result) : ?>
            <?php if ($result->getStatus() <= 0) : ?>
              <tr class="success">
                <th><?php print $result->getTitle(); ?></th>
                <td><?php print $result; ?></td>
              </tr>
            <?php elseif ($result->getStatus() == 1) : ?>
              <tr class="warning">
                <th><?php print $result->getTitle(); ?></th>
                <td><?php print $result; ?></td>
              </tr>
            <?php else : ?>
              <tr class="danger">
                <th><?php print $result->getTitle(); ?></th>
                <td>
                  <p><?php print $result; ?></p>
                  <p><?php print $result->getDescription(); ?></p>
                  <div class="panel panel-danger">
                    <div class="panel-heading">Remediation</div>
                    <div class="panel-body">
                      <p><?php print $result->getRemediation(); ?></p>
                    </div>
                  </div>
                </td>
              </tr>
            <?php endif ?>
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
