# Contributing to Onlifin

Thank you for your interest in contributing to Onlifin! This document provides guidelines for contributors.

## 🤝 How to Contribute

### Reporting Issues
1. Search existing issues first
2. Use issue templates when available
3. Provide detailed information with steps to reproduce

### Submitting Pull Requests
1. Fork the repository
2. Create a feature branch: `git checkout -b feature/your-feature-name`
3. Make your changes following our coding standards
4. Test thoroughly and update documentation
5. Submit a pull request with clear description

## 🏗️ Development Setup

```bash
# Clone and setup
git clone https://github.com/onlitec/onlifin.git
cd onlifin

# Start with Docker
docker-compose up -d

# Install dependencies
docker exec onlifin-app composer install
docker exec onlifin-app php artisan migrate
```

## 📝 Standards

- Follow PSR-12 for PHP
- Use Laravel best practices
- Write meaningful tests
- Update documentation
- Follow semantic versioning

## 🔒 Security

Report security issues privately to: galvatec@gmail.com

## 💰 Financial Logic

When working with financial data:
- Always use integers for monetary values (cents)
- Never use floating-point arithmetic for money
- Validate all calculations with tests
- See FINANCIAL_RULES.md for details

## 📞 Support

- GitHub Issues: Bug reports and features
- Email: galvatec@gmail.com
- Documentation: See README.md

## 📄 License

Contributions are licensed under MIT License.

Thank you for contributing! 🎉
