# WC GraphQL Member Dashboard


A production-ready headless WordPress plugin that exposes a custom GraphQL API via WPGraphQL, consumed by a modern Next.js member dashboard with NextAuth authentication.

🔗 **Live Demo:** https://wcgmd-dashboard.vercel.app  
📦 **Frontend Repo:** https://github.com/dilipraghavan/wcgmd-dashboard

---

## Features

### WordPress Plugin
- **Custom WPGraphQL Types** — `MemberProfile`, `MemberActivity`, `MemberNotification`, `MemberSettings`
- **Custom GraphQL Mutations** — `updateMemberProfile`, `updateMemberSettings`, `markNotificationRead`, `markAllNotificationsRead`
- **Custom Database Tables** — `wcgmd_user_activity` and `wcgmd_notifications` with proper indexing
- **Data Access Layer** — Clean DAL class separating SQL from resolvers
- **Extensibility Hooks** — Actions and filters for third-party integration

---

## Tech Stack

| Layer | Technology |
|---|---|
| Backend | WordPress, WPGraphQL, PHP 7.4+ |
| Authentication | WPGraphQL JWT Auth, NextAuth.js |
| Frontend | Next.js 14, TypeScript, React |
| GraphQL Client | graphql-request |
| Styling | CSS Modules |


---

## Installation

### Requirements
- WordPress 6.0+
- WPGraphQL 1.0+
- WPGraphQL JWT Authentication  
- PHP 7.4+

### Plugin Setup

1. Clone or download this repository
2. Copy the plugin folder to `wp-content/plugins/`
3. Install PHP dependencies:
   ```bash
   composer install
   ```
4. Activate the plugin in WordPress Admin → Plugins
5. Verify tables created: `wcgmd_user_activity` and `wcgmd_notifications`

### Developer Setup

```bash
# Clone the repo
git clone https://github.com/dilipraghavan/wc-graphql-member-dashboard.git
cd wc-graphql-member-dashboard

# Install PHP dev dependencies
composer install

# Set up coding standards
composer setup-phpcs

# Run code linting
composer phpcs

# Auto-fix issues
composer phpcbf
```

---

## GraphQL API

### Queries

```graphql
# Get full member dashboard data
query GetMemberDashboard($userId: ID!) {
  user(id: $userId) {
    name
    email
    memberProfile {
      bio
      phone
      location
      website
      socialLinks
    }
    memberSettings {
      emailNotifications
      marketingEmails
      dashboardTheme
      language
    }
    memberActivity(limit: 10) {
      id
      type
      description
      createdAt
    }
    memberNotifications(unreadOnly: false) {
      id
      type
      title
      message
      isRead
      createdAt
    }
  }
}
```

### Mutations

```graphql
# Update profile
mutation UpdateProfile($input: UpdateMemberProfileInput!) {
  updateMemberProfile(input: $input) {
    success
    profile {
      bio
      location
    }
  }
}

# Mark all notifications read
mutation MarkAllRead {
  markAllNotificationsRead(input: {}) {
    success
    count
  }
}
```

---
 
## License

MIT — see [LICENSE](LICENSE) for details.

---

*Built by [WP Shift Studio](https://wpshiftstudio.com) — WordPress & WooCommerce specialist.*
