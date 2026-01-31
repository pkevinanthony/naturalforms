# Multi-Tenant Form Builder SaaS

A complete multi-tenant form builder SaaS platform built on top of [OpnForm](https://github.com/OpnForm/OpnForm) architecture with NMI payment integration.

## Features

### Multi-Tenancy
- **Subdomain-based isolation** - Each tenant gets their own subdomain (tenant.app.domain.com)
- **Custom domain support** - Tenants can connect their own domains with DNS verification
- **Complete data isolation** - Tenant-scoped database queries prevent data leakage
- **Role-based access** - Owner, Admin, and Member roles per tenant

### Payment Integration (NMI)
- **Collect.js tokenization** - PCI SAQ-A compliant card handling
- **Customer Vault** - Secure storage of payment methods
- **Subscription billing** - Monthly and yearly recurring payments
- **One-time payments** - Support for single transactions
- **Webhook handling** - Real-time payment event processing

### Subscription Plans
| Feature | Free | Starter ($19/mo) | Professional ($49/mo) | Enterprise ($149/mo) |
|---------|------|------------------|----------------------|---------------------|
| Forms | 3 | 10 | 50 | Unlimited |
| Submissions/mo | 100 | 1,000 | 10,000 | Unlimited |
| Team Members | 1 | 3 | 10 | Unlimited |
| Custom Domain | ❌ | ❌ | ✅ | ✅ |
| White-Label | ❌ | ❌ | ✅ | ✅ |
| API Access | ❌ | ✅ | ✅ | ✅ |
| SSO/SAML | ❌ | ❌ | ❌ | ✅ |

### White-Label Branding
- Custom logo and favicon
- Brand colors (primary/secondary)
- Font family selection
- Custom CSS injection
- Remove "Powered by" badge

### Admin Features
- **Super Admin Panel** - Manage all tenants from a central dashboard
- **Tenant Statistics** - Overview of all tenants, users, and revenue
- **User Impersonation** - Log in as any tenant user for support
- **Tenant Suspension** - Suspend/activate tenants as needed

## Tech Stack

### Backend
- **Laravel 11** - PHP framework
- **MySQL 8** - Database
- **Redis** - Caching and queues
- **Laravel Sanctum** - API authentication

### Frontend
- **Nuxt 3** - Vue.js framework
- **Pinia** - State management
- **Tailwind CSS** - Styling
- **TypeScript** - Type safety

### Infrastructure
- **Docker** - Containerization
- **Nginx** - Reverse proxy
- **Let's Encrypt** - SSL certificates

## Quick Start

### Prerequisites
- Docker & Docker Compose
- Node.js 18+
- PHP 8.2+

### Installation

```bash
# Clone the repository
git clone https://github.com/yourusername/opnform-saas.git
cd opnform-saas

# Copy environment file
cp .env.example .env

# Update .env with your settings
# - Database credentials
# - NMI API keys
# - App URL

# Start Docker containers
docker-compose up -d

# Run database migrations
docker-compose exec api php artisan migrate

# Generate application key
docker-compose exec api php artisan key:generate

# Install frontend dependencies
cd client && npm install

# Start development server
npm run dev
```

### Access Points
- **Frontend:** http://localhost:3000
- **API:** http://localhost:8000
- **phpMyAdmin:** http://localhost:8080

## Configuration

### NMI Payment Gateway

Get your credentials from the [NMI merchant portal](https://secure.networkmerchants.com/):

```env
# Private API key (server-side only)
NMI_API_KEY=your_private_key

# Public tokenization key (safe for frontend)
NMI_TOKENIZATION_KEY=your_public_key

# Webhook secret for signature verification
NMI_WEBHOOK_SECRET=your_webhook_secret

# Enable test mode during development
NMI_TEST_MODE=true
```

### Multi-Tenancy

```env
# Central domain for the application
TENANT_CENTRAL_DOMAIN=app.yourdomain.com

# Trial period for new tenants (days)
TENANT_TRIAL_DAYS=14

# Enable custom domain feature
TENANT_CUSTOM_DOMAINS_ENABLED=true
```

## API Documentation

### Authentication
```bash
# Register
POST /api/auth/register
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123"
}

# Login
POST /api/auth/login
{
  "email": "john@example.com",
  "password": "password123"
}
```

### Subscriptions
```bash
# Get available plans
GET /api/billing/plans

# Subscribe to a plan
POST /api/billing/subscribe
{
  "plan": "professional",
  "payment_token": "token_from_collectjs",
  "billing_cycle": "monthly"
}

# Update payment method
PUT /api/billing/payment-method
{
  "payment_token": "new_token"
}
```

### Tenant Management
```bash
# Get current tenant
GET /api/tenant

# Update branding
PUT /api/tenant/branding
{
  "logo": "https://...",
  "primary_color": "#3B82F6",
  "hide_powered_by": true
}
```

## Project Structure

```
opnform-saas/
├── api/                    # Laravel Backend
│   ├── app/
│   │   ├── Models/         # Eloquent models
│   │   ├── Http/           # Controllers & middleware
│   │   └── Services/       # Business logic
│   ├── config/             # Configuration files
│   ├── database/           # Migrations & seeders
│   └── routes/             # API routes
│
├── client/                 # Nuxt Frontend
│   ├── components/         # Vue components
│   ├── pages/              # Route pages
│   ├── stores/             # Pinia stores
│   └── composables/        # Vue composables
│
├── docker-compose.yml      # Docker services
└── .env.example            # Environment template
```

## Webhook Setup

Configure your NMI merchant account to send webhooks to:
```
https://yourdomain.com/api/webhooks/nmi
```

Handled events:
- `recurring.success` - Subscription payment success
- `recurring.failure` - Payment failed
- `recurring.cancelled` - Subscription cancelled
- `transaction.sale.success` - One-time payment success

## Security

- **PCI Compliance** - Card data is tokenized via Collect.js and never touches your servers
- **Tenant Isolation** - Global query scopes ensure complete data separation
- **Webhook Verification** - HMAC signature verification for all webhooks
- **Rate Limiting** - API rate limiting per tenant

## Testing

```bash
# Run backend tests
cd api && php artisan test

# Run frontend tests
cd client && npm run test
```

## Deployment

### Production Checklist
- [ ] Set `APP_ENV=production`
- [ ] Set `APP_DEBUG=false`
- [ ] Configure production NMI credentials
- [ ] Set up SSL certificates
- [ ] Configure production database
- [ ] Set up Redis cluster
- [ ] Configure email provider
- [ ] Set up error monitoring
- [ ] Configure CDN
- [ ] Set up automated backups

## License

This project is based on OpnForm which is licensed under AGPLv3.

## Support

For questions or support, please open an issue on GitHub.

---

Built with ❤️ using [OpnForm](https://github.com/OpnForm/OpnForm), [Laravel](https://laravel.com), and [Nuxt](https://nuxt.com)
