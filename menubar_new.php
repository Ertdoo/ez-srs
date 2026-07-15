<!-- menubar_new.php -->
<!-- logout logic is now handled in land_logout.php -->
<?php // determine current script for nav highlighting
// determine current script for nav highlighting
// determine current script for nav highlighting
$current = basename(parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH)); ?>
<style>
  /* Dim non-active nav links and highlight the active section */
  #main-navbar .nav-link { opacity: 0.75; transition: opacity .15s ease, transform .12s ease, font-size .12s ease; }
  #main-navbar .nav-link.active-section { opacity: 1; font-weight: 700; transform: scale(1.06); font-size: 1.02rem; }
  #main-navbar .nav-link:hover { opacity: 0.95; transform: scale(1.03); }

  #main-navbar {
      border-bottom: 2px solid rgb(0, 255, 4);
  }

  /* Match the dropdown menus to the theme */
  #main-navbar .dropdown-menu {
      background-color: #1a1d2e;
      border: 1px solid rgb(0, 255, 4);
  }

  #main-navbar .dropdown-item {
      color: #ffffff;
  }

  #main-navbar .dropdown-item:hover {
      background-color: rgb(0, 255, 4);
      color: #0d101f;
  }
</style>

<nav class="navbar navbar-expand-md fixed-top" id="main-navbar">
  <div class="container-fluid">
    <?php if (isset($_SESSION["username"])): ?>
      <a class="navbar-brand" href="user_dashboard.php">Easy Spaced Repition - Home</a>
    <?php else: ?>
      <a class="navbar-brand" href="index.php">Easy Spaced Repition</a>
    <?php endif; ?>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
      data-bs-target="#navbarNavDarkDropdown" aria-controls="navbarNavDarkDropdown"
      aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarNavDarkDropdown">
      <ul class="navbar-nav me-auto">
        <?php if (isset($_SESSION["username"])): ?>
          <a class="nav-link<?php echo $current === "study.php"
              ? " active-section"
              : ""; ?>" href="study.php" style="color: Tomato">Study!</a>
          <a class="nav-link<?php echo $current === "decks.php"
              ? " active-section"
              : ""; ?>" href="decks.php" style="color: Orange">Manage decks</a>
          <a class="nav-link<?php echo $current === "deck_create.php"
              ? " active-section"
              : ""; ?>" href="deck_create.php" style="color: MediumSeaGreen">Create new deck</a>
          <li class="nav-item">
            <a class="nav-link<?php echo $current === 'deck_browse.php' ? ' active-section' : ''; ?>" href="deck_browse.php">Explore Decks</a>
          </li>
          <a class="nav-link<?php echo $current === "land_about.php"
              ? " active-section"
              : ""; ?>" href="land_about.php">About</a>
        <?php else: ?>
        <li class="nav-item">
          <a class="nav-link<?php echo $current === 'deck_browse.php' ? ' active-section' : ''; ?>" href="deck_browse.php">Explore Decks</a>
        </li>
          <a class="nav-link<?php echo $current === "land_about.php"
              ? " active-section"
              : ""; ?>" href="land_about.php">About</a>
        <?php endif; ?>
        <!-- <li class="nav-item dropdown ms-5">
          <a class="nav-link dropdown-toggle" href="#" id="projectsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            PROJECTS
          </a>
          <ul class="dropdown-menu dropdown-menu-dark" aria-labelledby="projectsDropdown">
          <li><a class="dropdown-item" href="land_about.php">About</a></li>
          </ul>
        </li> -->
      </ul>

      <div class="navbar-nav">
        <div class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            <span id="theme-icon" class="me-1"></span> Theme
          </a>
          <ul class="dropdown-menu dropdown-menu-end">
            <li>
              <button class="dropdown-item theme-option" data-theme="dark">
                <span class="me-2"></span> Dark Theme
              </button>
            </li>
            <li>
              <button class="dropdown-item theme-option" data-theme="light">
                <span class="me-2"></span> Light Theme
              </button>
            </li>
            <li><hr class="dropdown-divider"></li>
            <li>
              <button class="dropdown-item theme-option" data-theme="auto">
                <span class="me-2"></span> Auto (System)
              </button>
            </li>
          </ul>
        </div>


      <!-- Login/Logout Buttons -->
      <ul class="navbar-nav ms-auto">
        <?php if (isset($_SESSION["username"])): ?>
          <li class="nav-item ms-2 d-flex align-items-center gap-2">
            <?php echo htmlspecialchars($_SESSION["username"]); ?>
            <a class="btn btn-outline-danger btn-sm" href="land_logout.php">Logout</a>
          </li>
        <?php else: ?>
          <li class="nav-item ms-2 d-flex align-items-center"> <!-- gap-2 for button alignment -->
            <a class="nav-link<?php echo $current === "land_login.php"
                ? " active-section"
                : ""; ?>" href="land_login.php">Login</a>
          </li>
          <li class="nav-item ms-2 d-flex align-items-center">
            <a class="nav-link<?php echo $current === "land_register.php"
                ? " active-section"
                : ""; ?>" href="land_register.php">Register</a>
          </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>
