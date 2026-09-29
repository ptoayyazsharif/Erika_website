ErikaKPage - full site update
=============================

HOW TO INSTALL
  Unzip the contents into your public_html folder and let it overwrite when
  it asks. That is the whole job.

NOTHING YOU HAVE IS LOST
  Unzipping only adds files and replaces ones with the same name. It never
  deletes anything. Specifically:

    config.php        NOT in this zip. Your database settings are untouched.
    uploads/          Your uploaded photos and videos are NOT in this zip,
                      so every one of them stays exactly where it is.
    the database      Not touched at all. No migration, no SQL to run, no
                      setup step. Everything you have typed or chosen in the
                      admin stays.

  Anything you changed in the admin keeps winning over these files, because
  the admin saves to the database and the database is read first.

WHAT CHANGED SINCE THE LAST UPLOAD

  * The website has a blog.
      /blog                                       the article list
      /blog/closing-costs-in-georgia-explained    the first article

    "Blog" is now in the top menu and in the footer. The first article is
    about 1,500 words on closing costs in Georgia - what they are, who pays
    what, why two buyers get different numbers, earnest money, title
    insurance, where the Metro Atlanta market sits, and four habits that stop
    the surprises. It carries a dated, sourced market figure from the most
    recent Atlanta REALTORS market brief, four questions and answers, and the
    "explanatory only, not tax or legal advice" line.

  * The article can be listened to.
    There is a player under the cover photo: 11 minutes, the whole article
    read aloud in Erika's voice. The page says plainly that it is an AI
    narration. If Erika would rather record it herself, the admin now takes
    an MP3 upload that replaces it in one click - nothing else to change.

  * A new admin screen: Blog posts.
    Add, write, edit, publish and remove articles without a developer. A new
    post starts as a draft and stays invisible until "published" is ticked.
    Cover photo, narration, questions and answers, tags, and the Google title
    and description are all on that one screen. There is no limit on how many
    articles there can be.

  * Search-engine work the site had never had.
    Each of the 29 pages now has its own description instead of one shared
    line, and its own canonical address. Social previews (Open Graph and
    Twitter cards) work, so a link pasted into a message shows the right
    title and picture. The site now publishes structured data describing
    Erika as a real estate agent, the counties she serves, the blog, the
    article and its questions - the things Google reads to decide what a page
    is. There is a /robots.txt and a /sitemap.xml, both new, and the sitemap
    updates itself when an article is added.

  * A real "page not found" page.
    A mistyped address used to quietly show the home page with a 200 OK,
    which invites Google to index every typo as a copy of the front page.
    It now returns a proper 404 with a way back.

  * Four page titles that were missing.
    Sandy Springs / Roswell / Alpharetta, East Cobb / Marietta, Brookhaven /
    Decatur / Tucker and McDonough / Henry were all showing the generic site
    title in the browser tab and in search results. Fixed, and they now
    highlight in the menu like every other page.

  * Small fixes found along the way.
    Every photo, video and audio address is now absolute, which is what makes
    an address like /blog/<article> work at all. The home page portrait is no
    longer loaded at high priority on all the other pages.

NOTHING IN THE ADMIN IS NEEDED FOR THE ARTICLE
  The first article ships inside posts.php, so it is live the moment these
  files are uploaded. Editing it afterwards in the admin is optional.

STILL WAITING ON ERIKA
  See MEETING-2026-08-25-PENDING.md in this zip - the one-sheet PDF, the
  testimonial videos, the welcome video, the Erika Explains topic list, and
  the rest of the gallery photos.
