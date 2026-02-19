# WC GraphQL Member Dashboard

> **Portfolio Project #4 of 11 — WP Shift Studio**

A production-ready headless WordPress plugin that exposes a custom GraphQL API via WPGraphQL, consumed by a modern Next.js member dashboard with NextAuth authentication.

---

## Features

### WordPress Plugin
- **Custom WPGraphQL Types** — `MemberProfile`, `MemberActivity`, `MemberNotification`, `MemberSettings`
- **Custom GraphQL Mutations** — `updateMemberProfile`, `updateMemberSettings`, `markNotificationRead`, `markAllNotificationsRead`
- **Custom Database Tables** — `wcgmd_user_activity` and `wcgmd_notifications` with proper indexing
- **Data Access Layer** — Clean DAL class separating SQL from resolvers
- **Extensibility Hooks** — Actions and filters for third-party integration

### Next.js Frontend *(Phase 5–7)*
- Protected dashboard with NextAuth session management
- Profile page with editable fields and optimistic UI updates
- Notifications feed with read/unread toggle
- Activity log with filtering
- Settings panel with instant feedback
- GraphQL Codegen for end-to-end type safety

---

## Tech Stack

| Layer | Technology |
|---|---|
| Backend | WordPress, WPGraphQL, PHP 7.4+ |
| Authentication | WPGraphQL JWT Auth, NextAuth.js |
| Frontend | Next.js 14, TypeScript, React |
| GraphQL Client | Apollo Client |
| Type Safety | GraphQL Codegen |
| Styling | Tailwind CSS |

---

## Installation

### Requirements
- WordPress 6.0+
- WPGraphQL 1.0+
- WPGraphQL JWT Authentication (for Phase 4+)
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

## Project Structure

```
wc-graphql-member-dashboard/
├── wc-graphql-member-dashboard.php   # Plugin bootstrap + constants
├── includes/
│   ├── class-plugin.php              # Singleton orchestrator
│   ├── class-activator.php           # DB tables + demo data seeding
│   ├── graphql/
│   │   ├── class-type-registry.php   # Custom WPGraphQL types & fields
│   │   └── class-mutation-registry.php # Custom mutations
│   └── data/
│       └── class-user-data.php       # Data Access Layer
├── composer.json
├── phpcs.xml.dist
└── README.md
```

---

## Extensibility Hooks

```php
// Actions
do_action( 'wcgmd_profile_updated',      $user_id, $old_data, $new_data );
do_action( 'wcgmd_notification_created', $notification_id, $user_id );
do_action( 'wcgmd_notification_read',    $notification_id, $user_id );

// Filters
apply_filters( 'wcgmd_profile_fields',  $fields, $user_id );
apply_filters( 'wcgmd_activity_types',  $types );
apply_filters( 'wcgmd_settings_schema', $schema, $user_id );
```

---

## Build Phases

| Phase | Status | Focus |
|---|---|---|
| 1 | ✅ Complete | Plugin bootstrap, DB tables, GraphQL type stubs |
| 2 | 🔄 Next | Full GraphQL schema with resolvers |
| 3 | ⏳ Planned | Mutation implementation with auth checks |
| 4 | ⏳ Planned | JWT authentication setup |
| 5 | ⏳ Planned | Next.js project setup |
| 6 | ⏳ Planned | Dashboard pages |
| 7 | ⏳ Planned | GraphQL Codegen |
| 8 | ⏳ Planned | Polish & documentation |

---

## License

GPL-2.0+ — see [LICENSE](LICENSE) for details.

---

*Built by [WP Shift Studio](https://wpshiftstudio.com) — WordPress & WooCommerce specialist.*
