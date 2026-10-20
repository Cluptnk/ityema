<div class="navbar-header bg-img">
    <a class="navbar-brand pull-left perdekor-brand" rel="home" href="#" aria-label="Perdekor ana sayfa">
        <span>PERDEKOR</span>
    </a>
    <button type="button" class="navbar-toggle pull-left" data-toggle="collapse" data-target=".navbar-ex1-collapse"
        style="position: relative;z-index: 5;">
        <span class="sr-only">Menüyü aç veya kapat</span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
    </button>
</div>

<!-- Üst menü -->
<ul class="nav navbar-right top-nav">
    <li class="dropdown">
        <a href="#" class="dropdown-toggle" data-toggle="dropdown"><i class="fa fa-bell"></i> <span id="numberofevents"
                class="badge">0</span> <b class="caret"></b></a>
        <ul id="eventsontopheader" class="dropdown-menu alert-dropdown dropdown-menu-largo"><li></li></ul>
    </li>
    <li class="dropdown">
        <a href="#" class="dropdown-toggle" data-toggle="dropdown"><i class="fa fa-user"></i>
            <?php echo($_SESSION['name']) ?><b class="caret"></b></a>
        <ul class="dropdown-menu">
            <li><a href="logout.php"><i class="fa fa-fw fa-power-off"></i> <?php uploadW('esci') ?></a></li>
        </ul>
    </li>
</ul>
