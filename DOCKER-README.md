# 🏦 Onlifin - Personal Finance Management Platform

[![Docker Hub](https://img.shields.io/docker/v/onlitec/onlifin?label=Docker%20Hub)](https://hub.docker.com/r/onlitec/onlifin)
[![Docker Image Size](https://img.shields.io/docker/image-size/onlitec/onlifin/latest)](https://hub.docker.com/r/onlitec/onlifin)
[![Docker Pulls](https://img.shields.io/docker/pulls/onlitec/onlifin)](https://hub.docker.com/r/onlitec/onlifin)

## 📋 Description

Onlifin is a comprehensive personal finance management platform built with Laravel 11, featuring AI-powered transaction categorization, multi-company support, and advanced financial analytics. This Docker image provides a complete, production-ready environment for running the Onlifin application.

**Base Environment:**
- **OS**: Ubuntu 22.04 LTS
- **Web Server**: Nginx 1.28.0
- **PHP**: 8.2-FPM with optimized configuration
- **Process Manager**: Supervisor for service management
- **Size**: ~1.4GB (optimized for production)

## 🚀 Quick Start

### Basic Usage
```bash
# Pull the latest image
docker pull onlitec/onlifin:latest

# Run with default settings (development)
docker run -d -p 8080:80 --name onlifin onlitec/onlifin:latest
```

### Production Deployment
```bash
# Run with external database
docker run -d \
  -p 8080:80 \
  --name onlifin \
  -e DB_HOST=your-mysql-host \
  -e DB_DATABASE=onlifin \
  -e DB_USERNAME=your-username \
  -e DB_PASSWORD=your-password \
  -e APP_ENV=production \
  -e APP_DEBUG=false \
  onlitec/onlifin:latest
```

## 🏷️ Available Tags

| Tag | Description | Use Case |
|-----|-------------|----------|
| `latest` | Latest stable release | Production |
| `beta` | Beta/development version | Testing |
| `v1.0.0` | Specific version | Production (pinned) |
| `1f75484` | Commit-specific build | Development/debugging |

## 🔧 Environment Variables

### Required Variables
| Variable | Description | Default | Example |
|----------|-------------|---------|---------|
| `DB_HOST` | Database host | `localhost` | `mysql.example.com` |
| `DB_DATABASE` | Database name | `onlifin` | `onlifin_prod` |
| `DB_USERNAME` | Database user | `root` | `onlifin_user` |
| `DB_PASSWORD` | Database password | - | `secure_password` |

### Optional Variables
| Variable | Description | Default | Example |
|----------|-------------|---------|---------|
| `APP_ENV` | Application environment | `production` | `local`, `staging` |
| `APP_DEBUG` | Debug mode | `false` | `true` |
| `APP_URL` | Application URL | `http://localhost` | `https://onlifin.com` |
| `MAIL_MAILER` | Mail driver | `smtp` | `sendmail`, `mailgun` |
| `REDIS_HOST` | Redis host (optional) | `127.0.0.1` | `redis.example.com` |

## 📦 Docker Compose Example

```yaml
version: '3.8'

services:
  onlifin:
    image: onlitec/onlifin:latest
    ports:
      - "8080:80"
    environment:
      - DB_HOST=mysql
      - DB_DATABASE=onlifin
      - DB_USERNAME=onlifin_user
      - DB_PASSWORD=secure_password
      - APP_ENV=production
      - APP_DEBUG=false
    depends_on:
      - mysql
    volumes:
      - onlifin_storage:/var/www/html/storage
    restart: unless-stopped

  mysql:
    image: mariadb:10.6
    environment:
      - MYSQL_ROOT_PASSWORD=root_password
      - MYSQL_DATABASE=onlifin
      - MYSQL_USER=onlifin_user
      - MYSQL_PASSWORD=secure_password
    volumes:
      - mysql_data:/var/lib/mysql
    restart: unless-stopped

volumes:
  onlifin_storage:
  mysql_data:
```

## 🔌 Exposed Ports

| Port | Service | Description |
|------|---------|-------------|
| `80` | HTTP | Web application (Nginx) |
| `9000` | PHP-FPM | Internal PHP processing |

## 📁 Important Volumes

| Path | Description | Recommended Mount |
|------|-------------|-------------------|
| `/var/www/html/storage` | Application storage, logs, cache | Named volume |
| `/var/www/html/.env` | Environment configuration | Bind mount (optional) |

## 🏗️ Features

- **🤖 AI-Powered Categorization**: Automatic transaction categorization using multiple AI providers
- **🏢 Multi-Company Support**: Manage multiple businesses or personal accounts
- **📊 Advanced Analytics**: Comprehensive financial reports and dashboards
- **🔄 Bank Integration**: Import transactions from multiple sources
- **📱 Responsive Design**: Mobile-friendly interface
- **🔐 Security**: Built-in authentication and authorization
- **🌐 Multi-language**: Support for multiple languages

## 🔒 Security

This image is regularly updated and scanned for vulnerabilities:
- Base image: Ubuntu 22.04 LTS (security updates applied)
- PHP 8.2 with latest security patches
- Nginx with security-focused configuration
- No root processes (runs as `www` user)
- Minimal attack surface (only necessary packages installed)

## 🛠️ Health Check

The image includes a built-in health check:
```bash
# Check container health
docker ps --format "table {{.Names}}\t{{.Status}}"
```

## 📚 Documentation

- **Source Code**: https://github.com/onlitec/onlifin
- **Documentation**: https://github.com/onlitec/onlifin/blob/main/README.md
- **Issues**: https://github.com/onlitec/onlifin/issues
- **Changelog**: https://github.com/onlitec/onlifin/releases

## 🔄 Updates & Maintenance

- **Update Frequency**: Weekly security updates, monthly feature releases
- **Last Updated**: July 18, 2025
- **Maintenance Status**: ✅ Actively maintained
- **Support**: GitHub Issues or email: galvatec@gmail.com

## 🏷️ Version History

| Version | Date | Changes |
|---------|------|---------|
| `latest` | 2025-07-18 | Fixed permissions, SSL configuration, database structure |
| `beta` | 2025-07-18 | Development version with latest features |
| `1f75484` | 2025-07-18 | Permission fixes and Docker Hub improvements |

## 🤝 Contributing

We welcome contributions! Please see our [Contributing Guide](https://github.com/onlitec/onlifin/blob/main/CONTRIBUTING.md).

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](https://github.com/onlitec/onlifin/blob/main/LICENSE) file for details.

## 👥 Maintainers

- **Sandro Freire** ([@onlitec](https://github.com/onlitec)) - Lead Developer
- **Organization**: Onlitec Solutions

---

**Need help?** Open an issue on [GitHub](https://github.com/onlitec/onlifin/issues) or contact us at galvatec@gmail.com
