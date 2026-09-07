# Ocasio Hide Version

> Lightweight WordPress plugin to completely remove WordPress version numbers from source code, feeds, and asset URLs.

## Overview

Broadcasting your exact WordPress version number helps hacker bots profile your site and target known vulnerabilities. **Ocasio Hide Version** removes generator meta tags, cleans RSS and Atom feeds, and strips version query strings from styles and scripts.

## Features

* **Generator Tag Removal:** Automatically strips the WordPress version generator tag from your site's `<head>`.
* **Feed Security:** Removes version numbers from RSS, Atom, RDF, and OPML feeds.
* **Asset Query String Cleanup:** Strips `?ver=x.x.x` query strings from stylesheets, scripts, and modules.
* **Zero Front-End Assets:** Pure PHP execution with 0 bytes of extra CSS or JavaScript added to public pages.
* **Ocasio Suite Integration:** Toggle the tool on or off through the centralized **Ocasio Plugins -> Dashboard**.

## Installation

1. Download the latest `ocasio-hide-version.zip` file from [Releases](https://github.com/kevinocasio/ocasio-hide-version/releases).
2. In your WordPress admin dashboard, navigate to **Plugins -> Add New Plugin -> Upload Plugin**.
3. Choose the downloaded `.zip` file and click **Install Now**.
4. Click **Activate Plugin**.
5. Confirm protection under **Ocasio Plugins -> Dashboard** in your sidebar.

---

## Author & Resources

* **Author:** [Kevin Ocasio](https://kevinocasio.com/)
* **Plugin Page:** [Ocasio Hide Version on KevinOcasio.com](https://kevinocasio.com/wordpress-plugins/ocasio-hide-version/)
* **WordPress Plugins:** [Free WordPress Plugin Directory](https://kevinocasio.com/wordpress-plugins/)
* **Software Portfolio:** [Live Projects & Digital Assets](https://kevinocasio.com/portfolio/)
* **Tools & Resources:** [Recommended Tech Stack & Tools](https://kevinocasio.com/tools/)
* **License:** GPL-2.0-or-later
