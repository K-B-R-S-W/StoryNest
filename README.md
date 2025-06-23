# StoryNest

A creative community for writers to share, grow and inspire each other. StoryNest is a feature rich platform where storytellers can publish stories, join challenges, connect with other writers and track their progress through achievements and community engagement.

---

## Table of Contents
- [Features](#features)
- [Getting Started](#getting-started)
- [Usage](#usage)
- [Screenshots](#screenshots)
- [Contributing](#contributing)
- [License](#license)
- [Contact](#contact)

---

## Features
- **User Registration & Login**: Secure authentication for writers.
- **Story Publishing**: Write, edit, and publish stories with a rich text editor (Quill.js).
- **Categories & Series**: Organize stories by genre and series.
- **Bookmarks**: Save favorite stories for later reading.
- **Writing Challenges**: Participate in and suggest community writing challenges.
- **Achievements**: Earn badges for writing milestones and community engagement.
- **Comments & Likes**: Engage with stories through comments and likes.
- **Followers & Following**: Build your writing network.
- **Profile Customization**: Add bio, social links, and profile images.
- **Dark Mode**: Toggle between light and dark themes.
- **Notifications**: Stay updated on likes, follows, and challenges.
- **Newsletter**: Opt-in for weekly writing tips and highlights.
- **Responsive Design**: Works on desktop and mobile devices.
- **StoryNest AI Chat Widget**: Chat with the StoryNest AI assistant for writing help and inspiration.

---

## Getting Started

### Prerequisites
- PHP 7.4+
- MySQL/MariaDB
- Web server (Apache/Xampp recommended)

### Installation
1. **Clone the repository:**
   ```bash
   git clone https://github.com/K-B-R-S-W/StoryNest.git
   cd StoryNest
   ```
2. **Database Setup:**
   - Import `dtabse.sql` into your MySQL server:
     ```bash
     mysql -u root -p < dtabse.sql
     ```
   - Or run `setup.php` in your browser to auto-create tables and insert defaults.
3. **Configure Database:**
   - Edit `config.php` with your database credentials if needed.
4. **Set Permissions:**
   - Ensure the `uploads/` directory is writable for profile images and story assets.
5. **Start the Server:**
   - Use XAMPP, MAMP, or your preferred local server stack.
   - Visit `http://localhost/StoryNest` in your browser.

---

## Usage
- **Register** for an account and set up your profile.
- **Create stories** using the rich editor, organize them into categories or series.
- **Join challenges** to compete and improve your writing.
- **Bookmark, like, and comment** on stories you enjoy.
- **Earn achievements** and grow your following.
- **Customize your experience** with dark mode and notification preferences.

---

## Contributing
Contributions are welcome! Please fork the repository and submit a pull request. For major changes, open an issue first to discuss what you would like to change.

---

## License
This project is licensed under the MIT License. See the [LICENSE](LICENSE) file for details.

---

## Contact
- **GitHub:** [K-B-R-S-W](https://github.com/K-B-R-S-W)
- For questions or support, open an issue on GitHub.