<?php include ("connect.php"); ?>
<?php include "header.php"; ?>

<head>
    <title>About - Easy Spaced Repitition</title>
</head>
<body>
    <main class="container py-4">

        <section id="intro" class="mb-5">
            <br>
            <h1>About ez-srs</h1>
            <p>
                A collaborative spaced repetition learning tool. An Anki clone based on PHP and MySQL,
                but decks can be shared and edited by multiple users natively, with a Git style proposal and review system instead of free for all chaos.
                Its highly recommended to finish all your due cards everyday, to make the most of the algorithm. I may add a feature to pause decks and/or adjust the amount of new cards shown each day.
            </p>
            This project is open source ig. -> <a href="https://github.com/Ertdoo/ez-srs">GitHub</a>
        </section>

        <hr>

        <section id="how-srs-works" class="mb-5">
            <h2>How spaced repetition works</h2>
            <div class="d-flex flex-column flex-md-row align-items-center justify-content-center gap-2 py-4">

              <div class="card text-center border-secondary-subtle" style="width: 9rem;">
                <div class="card-body py-3">
                  <div class="fw-semibold">New item</div>
                  <div class="small text-muted">appears</div>
                </div>
              </div>

              <i class="bi bi-arrow-right fs-4 text-muted d-none d-md-block"></i>
              <i class="bi bi-arrow-down fs-4 text-muted d-md-none"></i>

              <div class="card text-center text-white bg-primary" style="width: 10rem;">
                <div class="card-body py-3">
                  <div class="fw-semibold">Answer &amp; rate</div>
                  <div class="small" style="opacity: .85;">Again / Hard / Good / Easy</div>
                </div>
              </div>

              <i class="bi bi-arrow-right fs-4 text-muted d-none d-md-block"></i>
              <i class="bi bi-arrow-down fs-4 text-muted d-md-none"></i>

              <div class="card text-center border-info-subtle" style="width: 10.5rem;">
                <div class="card-body py-3">
                  <div class="fw-semibold">Reschedule</div>
                  <div class="small text-muted">Shorter or longer interval based on ease factor</div>
                </div>
              </div>

            </div>

            <div class="text-center text-muted small">
              <i class="bi bi-arrow-repeat"></i> When the due date arrives, the cycle repeats
            </div>

            <h5>What is "ease factor"?</h5>
            <p>
                Every card has an ease factor, starting at 2.5. It's a
                multiplier that controls how quickly a card's interval grows
                once you've learned it. A higher ease factor means bigger intervals between
                reviews; a lower one means the card is shown more often.
            </p>
            <p>
                Your rating nudges the ease factor slightly each time you review a card:
            </p>
            <ul>
                <li style="color: Tomato"><strong>Again</strong> —> resets the card back into learning stage, ease factor is lowered by 0.2</li>
                <li style="color: Orange"><strong>Hard</strong> —> small interval bump, ease factor is lowered by 0.15</li>
                <li style="color: MediumSeaGreen"><strong>Good</strong> —> large interval bump, ease factor stays the same</li>
                <li style="color: DodgerBlue"><strong>Easy</strong> —> very large interval bump, ease factor is raised by 0.15</li>
            </ul>
            The formula used to calculate intervals (SM2 algorithm): <br>
            <code>
                new interval = (old interval + days late / 2) × ease factor
            </code>
        </section>

        <hr>

        <!-- ============================================= -->
        <!-- HOW COLLABORATION WORKS -->
        <!-- ============================================= -->
        <section id="how-collab-works" class="mb-5">
            <h2>How collaboration works</h2>
            <p>
                Every deck has an owner, who can invite other users to edit it. Editors can add, edit, or delete cards, but changes are not applied to the deck immediately.
                Changes are reviewed by the owner before merging. Only the deck owner can review and merge proposals. All actions are logged into my database.
            </p>

            <h5>Roles</h5>
            <ul>
                <li><strong>Owner</strong> —> Owns the deck, has absolute control over everything</li>
                <li><strong>Editor</strong> —> Can submit proposals to the owner of the deck</li>
                <li><strong>Subscriber</strong> —> Can view & study the deck but cannot edit it</li>
            </ul>

            <h5>Subscribing</h5>
            <p>
                When you subscribe to a deck, you become a subscriber. Subscribers can view & study the deck but cannot edit it.
            </p>

            <h5>Forking</h5>
            <p>
                When you fork a deck, you create a copy of it under your own account. You can then edit the fork independently of the original deck; you are the owner of the fork.
            </p>
        </section>

        <hr>

        <!-- ============================================= -->
        <!-- FAQ -->
        <!-- ============================================= -->
        <section id="faq" class="mb-5">
            <h2>FAQ</h2>

            <div class="accordion" id="aboutFaqAccordion">

                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                            i just wanted to use this lmao
                        </button>
                    </h2>
                    <div id="faq1" class="accordion-collapse collapse" data-bs-parent="#aboutFaqAccordion">
                        <div class="accordion-body">
                            hi
                        </div>
                    </div>
                </div>

                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                            if theres actual questions i will put them in
                        </button>
                    </h2>
                    <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#aboutFaqAccordion">
                        <div class="accordion-body">
                            sigh
                        </div>
                    </div>
                </div>

                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                            what is the meaning of life
                        </button>
                    </h2>
                    <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#aboutFaqAccordion">
                        <div class="accordion-body">
                            to install more linux distros
                        </div>
                    </div>
                </div>

                <!-- duplicate accordion-item blocks above for more questions -->

            </div>
        </section>

        <hr>

        <section id="creator" class="mb-5">
            <h2>About the creator</h2>
            <p>
                Built for software development units 3&4 SAT
            </p>
        </section>

        <section id="contact" class="mb-5">
            <h2>Contact</h2>
            <p>
                Email:
                <a href="mailto:ezsrs.nixos@gmail.com">ezsrs.nixos@gmail.com</a>
                <br>
                GitHub:
                <a href="https://github.com/Ertdoo/ez-srs">github.com/Ertdoo/ez-srs</a>
            </p>
        </section>

    </main>
</body>

<?php include "footer.php" ?>
