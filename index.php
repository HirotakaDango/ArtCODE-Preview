<?php
// Initialize variables
$websiteUrl = '';
$folderPath = '';
$thumbPath = '';

// SQLite database connection
$db = new SQLite3('your_database.sqlite'); // Replace with your actual database file

// Create settings table if it doesn't exist
$createTableQuery = "CREATE TABLE IF NOT EXISTS settings (
  id INTEGER PRIMARY KEY,
  website_url TEXT,
  folder_path TEXT,
  thumb_path TEXT
)";
$db->exec($createTableQuery);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $websiteUrl = $_POST['website_url'];
  $folderPath = $_POST['folder_path'];
  $thumbPath = $_POST['thumb_path']; // Corrected this line

  // Update or insert URL and path in the database
  $stmt = $db->prepare("INSERT OR REPLACE INTO settings (id, website_url, folder_path, thumb_path) VALUES (1, :website_url, :folder_path, :thumb_path)");
  $stmt->bindValue(':website_url', $websiteUrl);
  $stmt->bindValue(':folder_path', $folderPath);
  $stmt->bindValue(':thumb_path', $thumbPath);
  $stmt->execute();

  // Redirect to prevent form resubmission
  header('Location: ' . $_SERVER['REQUEST_URI']);   
  exit();
}

// Fetch website URL, folder path, and thumb path from the database
$selectQuery = "SELECT website_url, folder_path, thumb_path FROM settings WHERE id = 1"; // Corrected this line
$result = $db->querySingle($selectQuery, true);

if ($result) {
  $websiteUrl = $result['website_url'];
  $folderPath = $result['folder_path'];
  $thumbPath = $result['thumb_path'];
}
?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ArtCODE - Preview</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4bw+/aepP/YC94hEpVNVgiZdgIC5+VKNBQNGCHeKRQN+PtmoHDEXuppvnDJzQIu9" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
  </head>
  <body>
    <form method="POST">
      <nav class="navbar navbar-expand-lg bg-body-tertiary shadow">
        <div class="container-fluid gap-2 justify-content-end">
          <a class="navbar-brand me-auto fw-bold text-secondary" href="/">ArtCODE</a>
          <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
          </button>
          <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0 gap-1"> <!-- Changed me-auto to ms-auto -->
              <li class="nav-item">
                <button id="themeToggle" class="btn btn-primary fw-bold w-100">
                  <i id="themeIcon" class="bi"></i> toggle theme
                </button>
              </li>
              <li class="nav-item">
                <input class="form-control" type="text" name="website_url" value="<?php echo $websiteUrl; ?>" placeholder="website url">
              </li>
              <li class="nav-item">
                <input class="form-control" type="text" name="folder_path" value="<?php echo $folderPath; ?>" placeholder="folder path">
              </li>
              <li class="nav-item">
                <input class="form-control" type="text" name="thumb_path" value="<?php echo $thumbPath; ?>" placeholder="thumbnail path">
              </li>
              <li class="nav-item">
                <button class="btn btn-primary w-100 fw-bold">save</button>
              </li>
            </ul>
          </div>
        </div>
      </nav>
    </form>
    <?php
      $sourceApiUrl = $websiteUrl . '/api.php'; // Construct API URL based on user input
      $json = file_get_contents($sourceApiUrl);
      $images = json_decode($json, true);
    ?>

    <div class="images mt-2">
      <?php foreach ($images as $image): ?>
        <a class="imagesA rounded" href="#" data-bs-toggle="modal" data-bs-target="#imageModal<?= $image['id']; ?>">
          <img class="imagesImg lazy-load" data-src="<?= $websiteUrl . '/' . $thumbPath . '/' . $image['filename']; ?>" alt="<?= $image['title']; ?>">
        </a>
        <div class="modal fade" id="imageModal<?= $image['id']; ?>" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
          <div class="modal-dialog modal-fullscreen">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title fw-bold" id="imageModalLabel"><?= $image['title']; ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body text-center">
                <div class="row featurette">
                  <div class="col-md-7 order-md-1">
                    <img class="img-fluid lazy-load mb-2" data-src="<?= $websiteUrl . '/' . $folderPath . '/' . $image['filename']; ?>" alt="<?= $image['title']; ?>">
                  </div>
                  <div class="col-md-5 order-md-2">
                    <p class="text-start fw-semibold" style="word-wrap: break-word;">
                      <?php
                        $messageText = $image['imgdesc'];
                        $messageTextWithoutTags = strip_tags($messageText);
                        $pattern = '/\bhttps?:\/\/\S+/i';
    
                        $formattedText = preg_replace_callback($pattern, function ($matches) {
                          $url = htmlspecialchars($matches[0]);
                          return '<a href="' . $url . '">' . $url . '</a>';
                        }, $messageTextWithoutTags);
    
                        $formattedTextWithLineBreaks = nl2br($formattedText);
                        echo $formattedTextWithLineBreaks;
                      ?>
                    </p>
                    <div class="btn-group gap-2 mt-2">
                      <a class="btn btn-sm btn-secondary rounded-3 fw-bold opacity-50" href="<?= $websiteUrl . '/' . $folderPath . '/' . $image['filename']; ?>" download>Download Image</a>
                      <a class="btn btn-sm btn-secondary rounded-3 fw-bold opacity-50" href="<?= $websiteUrl; ?>/image.php?artworkid=<?= $image['id']; ?>" target="_blank">Original Source</a>
                    </div>
                    <div class="container mt-2">
                      <?php
                        $tags = explode(',', $image['tags']);
                        foreach ($tags as $tag) {
                          $tag = trim($tag);
                          if (!empty($tag)) {
                      ?>
                        <a href="<?= $websiteUrl; ?>/tagged_images.php?tag=<?php echo urlencode($tag); ?>"
                          class="btn btn-sm btn-secondary mb-1 rounded-3 fw-bold opacity-50">
                          <i class="bi bi-tags-fill"></i> <?php echo $tag; ?>
                        </a>
                      <?php }
                      } ?>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
    <div class="mt-5"></div>
    <style>
      .images {
        display: grid;
        grid-template-columns: repeat(2, 1fr); /* Two columns in mobile view */
        grid-gap: 3px;
        justify-content: center;
        margin-right: 3px;
        margin-left: 3px;
      }
      
      .text-stroke {
        -webkit-text-stroke: 1px;
      }

      @media (min-width: 768px) {
        /* For desktop view, change the grid layout */
        .images {
          grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        }
      }

      .imagesA {
        display: block;
        overflow: hidden;
      }

      .imagesImg {
        width: 100%;
        height: auto;
        object-fit: cover;
        height: 200px;
        transition: transform 0.5s ease-in-out;
      }
    </style>
    <script>
      // Get the theme toggle button, icon element, and html element
      const themeToggle = document.getElementById('themeToggle');
      const themeIcon = document.getElementById('themeIcon');
      const htmlElement = document.documentElement;

      // Check if the user's preference is stored in localStorage
      const savedTheme = localStorage.getItem('theme');
      if (savedTheme) {
        htmlElement.setAttribute('data-bs-theme', savedTheme);
        updateThemeIcon(savedTheme);
      }

      // Add an event listener to the theme toggle button
      themeToggle.addEventListener('click', () => {
        // Toggle the theme
        const currentTheme = htmlElement.getAttribute('data-bs-theme');
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
    
        // Apply the new theme
        htmlElement.setAttribute('data-bs-theme', newTheme);
        updateThemeIcon(newTheme);

        // Store the user's preference in localStorage
        localStorage.setItem('theme', newTheme);
      });

      // Function to update the theme icon
      function updateThemeIcon(theme) {
        if (theme === 'dark') {
          themeIcon.classList.remove('bi-moon-fill');
          themeIcon.classList.add('bi-sun-fill');
        } else {
          themeIcon.classList.remove('bi-sun-fill');
          themeIcon.classList.add('bi-moon-fill');
        }
      }
    </script>
    <script>
      let lazyloadImages = document.querySelectorAll(".lazy-load");
      let imageContainer = document.getElementById("image-container");

      // Set the default placeholder image
      const defaultPlaceholder = "icon/bg.png";

      if ("IntersectionObserver" in window) {
        let imageObserver = new IntersectionObserver(function(entries, observer) {
          entries.forEach(function(entry) {
            if (entry.isIntersecting) {
              let image = entry.target;
              image.src = image.dataset.src;
              imageObserver.unobserve(image);
            }
          });
        });

        lazyloadImages.forEach(function(image) {
          image.src = defaultPlaceholder; // Apply default placeholder
          imageObserver.observe(image);
          image.style.filter = "blur(5px)"; // Apply initial blur to all images
          image.addEventListener("load", function() {
            image.style.filter = "none"; // Remove blur after image loads
          });
        });
      } else {
        let lazyloadThrottleTimeout;

        function lazyload() {
          if (lazyloadThrottleTimeout) {
            clearTimeout(lazyloadThrottleTimeout);
          }
          lazyloadThrottleTimeout = setTimeout(function() {
            let scrollTop = window.pageYOffset;
            lazyloadImages.forEach(function(img) {
              if (img.offsetTop < window.innerHeight + scrollTop) {
                img.src = img.dataset.src;
                img.classList.remove("lazy-load");
              }
            });
            lazyloadImages = Array.from(lazyloadImages).filter(function(image) {
              return image.classList.contains("lazy-load");
            });
            if (lazyloadImages.length === 0) {
              document.removeEventListener("scroll", lazyload);
              window.removeEventListener("resize", lazyload);
              window.removeEventListener("orientationChange", lazyload);
            }
          }, 20);
        }

        document.addEventListener("scroll", lazyload);
        window.addEventListener("resize", lazyload);
        window.addEventListener("orientationChange", lazyload);
      }

      // Infinite scrolling
      let loading = false;

      function loadMoreImages() {
        if (loading) return;
        loading = true;

        // Simulate loading delay for demo purposes
        setTimeout(function() {
          for (let i = 0; i < 10; i++) {
            if (lazyloadImages.length === 0) {
              break;
            }
            let image = lazyloadImages[0];
            imageContainer.appendChild(image);
            lazyloadImages = Array.from(lazyloadImages).slice(1);
          }
          loading = false;
        }, 1000);
      }

      window.addEventListener("scroll", function() {
        if (window.innerHeight + window.scrollY >= imageContainer.clientHeight) {
          loadMoreImages();
        }
      });

      // Initial loading
      loadMoreImages();
    </script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.min.js" integrity="sha384-Rx+T1VzGupg4BHQYs2gCW9It+akI2MM/mndMCy36UVfodzcJcF0GGLxZIzObiEfa" crossorigin="anonymous"></script>
  </body>
</html>
