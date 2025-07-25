<?php
require 'polaris.php';

$ini = parse_ini_file("config.ini", true);
if ($ini === false) {
  http_response_code(500);
  die("Failed to read config file.");
}

if (isset($ini['config'])) {
  $config = $ini['config'];
  $required = ['base', 'apikey', 'apiid', 'domain', 'staffuser', 'staffpass', 'collection', 'syndetics', 'promo', 'workstationid', 'orgid', 'userid', 'opac'];
  foreach ($required as $key) {
    if (!array_key_exists($key, $config)) {
      http_response_code(500);
      die("Missing required key '$key' in [config] section.");
    }
    $$key = $config[$key];
  }
} else {
  http_response_code(500);
  die("The section [config] is missing in config file.");
}
?>

<?php if ($_SERVER['REQUEST_METHOD'] === 'GET'): ?>
  <head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0"/>
    <link href="https://fonts.googleapis.com/css?family=Open+Sans+Condensed:300" rel="stylesheet">
    <link rel="stylesheet" href="plot.css">
    <link rel="stylesheet" href="blurt.min.css" lazyload>
  </head>
<?php endif; ?>

<?php if (isset($_GET['q']) || isset($_GET['limit'])): ?>
  <?php
  $items = search($_GET['q'], rawurlencode($_GET['limit']));
  $title = 'Search Results';
  if (isset($_GET['title'])) {
    $title = htmlentities(rawurldecode($_GET['title']));
  }
  ?>
  <div class="sticky">
    <img alt="Library Logo" src="logo.png" />
    <span id="title"><?= $title ?></span>
    <button class="back" id="back" onclick="history.back()">BACK</button>
  </div>

  <div id="fiction" onscroll="reset()">
    <div class="field__items">
      <div class="paragraph paragraph--type--book-river">
        <?php foreach ($items->BibSearchRows as $row): ?>
          <?php
          $oclc = $row->OCLC;
          $isbn = $row->ISBN;
          $upc = $row->UPC;
          $title = rtrim($row->Title, ".");
          $id = $row->ControlNumber;
          $call = $row->CallNumber;
          $url = $opac . $title;

          if (strlen($oclc) > 0) {
            $url = $opac . $oclc;
            $isbn = "";
          } elseif (strlen($upc) > 0) {
            $url = $opac . $upc;
          } elseif (strlen($isbn) > 0) {
            $url = $opac . $isbn;
          }

          $oclcCover = 'items/OCLC' . $oclc . '.jpg';
          $upcCover = 'items/UPC' . $upc . '.jpg';
          $isbnCover = 'items/ISBN' . $isbn . '.jpg';

          if (file_exists('public/' . $oclcCover)) {
            $cover = $oclcCover;
          } elseif (file_exists('public/' . $upcCover)) {
            $cover = $upcCover;
          } elseif (file_exists('public/' . $isbnCover)) {
            $cover = $isbnCover;
          } elseif ($syndetics && ($isbn || $upc || $oclc)) {
            $isbnParam = 'isbn=%2Flc.gif';
            $oclcParam = '';
            $upcParam = '';
            if (strlen($isbn)) {
              $isbnParam = 'isbn=' . $isbn . '%2Fmc.gif';
            }
            if (strlen($oclc)) {
              $oclcParam = '&oclc=' . $oclc;
            }
            if (strlen($upc)) {
              $upcParam = '&upc=' . $upc;
            }
            $cover = 'https://secure.syndetics.com/index.aspx?' . $isbnParam . $oclcParam . $upcParam . '&client=' . $syndetics . '&nicaption=' . $title;
          } else {
            $cover = 'items/default.png';
          }

          $description = $row->Description;
          $json = json_encode($row);
          $status = 'unavailable';
          if ($row->LocalItemsIn > 0 && $row->CurrentHoldRequests == 0) {
              $status = 'available';
          }
          ?>
          <div class="field__item"
             data-call="<?= urlencode($call) ?>"
             data-id="<?= $id ?>"
             data-title="<?= htmlentities($title) ?>"
             data-cover="<?= $cover ?>"
             data-url="<?= $url ?>"
             data-description="<?= htmlentities($description) ?>"
             data-json="<?= rawurlencode($json) ?>"
             onclick="createDescription(this);">
            <div class="node__content book-list--item--cover">
              <img alt="<?= htmlentities($title) ?>" class="center <?= $status ?>" src="<?= $cover ?>">
            </div>
            <div class="book-list--item--teaser"><a><?= htmlentities($title) ?></a></div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <div id="l-overlay" onclick="document.body.classList.toggle('access');reset();">&#9855;</div>
  <div id="r-overlay" onclick="initial()">&#8617;</div>
  <div class="modal" id="communicoModal">
    <div class="modal-content">
      <span id="closeModal">&times;</span>
      <div id="filters"></div>
      <div id="communicoWrapper"></div>
    </div>
  </div>

  <script src="plot.js" defer></script>
  <script src="blurt.min.js" defer></script>

<?php elseif (isset($_POST['hold']) && isset($_POST['card'])): ?>
  <?php
    $hold = $_POST["hold"];
    $card = $_POST["card"];
    $result = placeHold($hold,$card);
    echo $result;
  ?>

<?php elseif (isset($_GET['debug'])): ?>
  <pre><?= json_encode(getInfo('organizations/all'), JSON_PRETTY_PRINT) ?></pre>
  <pre><?= json_encode(getInfo('materialtypes'), JSON_PRETTY_PRINT) ?></pre>
  <pre><?= json_encode(getInfo('marctypeofmaterials'), JSON_PRETTY_PRINT) ?></pre>
  <pre><?= json_encode(getInfo('limitfilters'), JSON_PRETTY_PRINT) ?></pre>

<?php else : ?>
  <div class="sticky">
    <img alt="Library Logo" src="logo.png" />
    <span id="title"><span id="sub">DISCOVER<br>The Library of Things</span></span>
  </div>

  <center>
    <div class="promo fadein">
      <iframe scrolling="no" width="942" height="530" frameBorder="0" src="<?= $promo ?>"></iframe>
    </div>
  </center>

  <div id="l-overlay" onclick="document.body.classList.toggle('access');reset();">&#9855;</div>
  <div id="r-overlay" onclick="initial()">&#8617;</div>

  <div id="fiction">
    <form id="search" method="get">
      <input type="text" autocomplete="off" placeholder="Search..." name="q" />
      <button type="submit">
        <svg class="svg-icon search-icon" aria-labelledby="search-title search-desc" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 19.9 19.7">
          <title id="search-title">Search Icon</title>
          <desc id="search-desc">A magnifying glass icon.</desc>
          <g class="search-path" fill="none" stroke="#FFFFFF">
            <path stroke-linecap="square" d="M18.5 18.3l-5.4-5.4"/>
            <circle cx="8" cy="8" r="7"/>
          </g>
        </svg>
      </button>
    </form>

    <div class="field__items">
      <div class="paragraph paragraph--type--book-river">
        <?php foreach ($ini as $section => $options): ?>
          <?php if ($section === 'config') continue; ?>

          <?php
            $limit = rawurlencode($options['limit']);
            $link = '?q=*&limit=' . $limit . '&title=' . rawurlencode($options['name']);
            $cover = 'images/' . $options['image'] . '1.jpg';
          ?>

          <div class="field__item" onclick="location.href='<?= $link ?>'">
            <div class="node__content book-list--item--cover">
              <img alt="<?= htmlentities($options['name']) ?>" data-image="<?= htmlentities($options['image']) ?>" class="center" src="<?= $cover ?>" />
            </div>
            <div class="book-list--item--teaser"><a><?= htmlentities($options['name']) ?></a></div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
  <script src="plot.js" defer></script>
<?php endif; ?>
