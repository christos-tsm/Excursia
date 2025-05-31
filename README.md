<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

# Excursia - Tourism Management System

## Λειτουργίες

### Σύστημα Αυθεντικοποίησης με Διπλό Email

Η εφαρμογή υποστηρίζει σύνδεση χρηστών με δύο διαφορετικά email addresses:

1. **Προσωπικό Email**: Το email που εισάγει ο χρήστης κατά την εγγραφή του προσωπικού του λογαριασμού
2. **Επιχειρησιακό Email**: Το email της επιχείρησης που εισάγεται κατά τη δημιουργία του tenant

#### Πώς Λειτουργεί

Όταν ένας χρήστης προσπαθεί να συνδεθεί:

1. Το σύστημα ψάχνει πρώτα για χρήστη με το δοθέν email
2. Αν δεν βρει, ψάχνει για tenant με αυτό το email
3. Αν βρει tenant, χρησιμοποιεί το `owner_id` για να βρει τον αντίστοιχο χρήστη
4. Ελέγχει τον κωδικό πρόσβασης (πάντα του χρήστη, όχι του tenant)
5. Ελέγχει αν το tenant είναι ενεργό
6. Συνδέει τον χρήστη

#### Παράδειγμα

Αν κατά την εγγραφή δόθηκαν:
- Προσωπικό email: `john@personal.com`
- Επιχειρησιακό email: `info@company.com`
- Κωδικός: `mypassword123`

Ο χρήστης μπορεί να συνδεθεί με:
- Email: `john@personal.com`, Password: `mypassword123` **✓**
- Email: `info@company.com`, Password: `mypassword123` **✓**

#### Ασφάλεια

- Ο κωδικός πρόσβασης ελέγχεται πάντα έναντι του χρήστη (owner)
- Η σύνδεση επιτρέπεται μόνο αν το tenant είναι ενεργό
- Rate limiting εφαρμόζεται για προστασία από brute force επιθέσεις

#### Tests

Η λειτουργία καλύπτεται με automated tests:
- `test_owner_can_login_with_personal_email()`
- `test_owner_can_login_with_business_email()`
- `test_inactive_tenant_owner_cannot_login()`
