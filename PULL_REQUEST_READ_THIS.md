## Description

Middleware Log Visits automatically, but does not prevent from duplicate or multiple visits entries into database.

When user refreshes the `vistor()->visit()` is fired everytime, resulting in increase in database request and log every time.

The Middleware is modified in a simple way,

- Just added a simple check before calling `vistor()->visit()`
- Every part of the code remained same and working as intended.
- I just added `session()->has('visit')` which will check for the existing `visit` key in session, if it exists it won't fire `visitor()->visit()`.

How it works

* When User hit the route protected with `LogVisits` middleware.
* It will check for the visit key, which contins `IP` and `URL` in it.
* If visit key does not exist, A `visit` key will be created and stored in session.
* If Session key exist, it will check if the IP and URL are the same as previous, it it is not, it will fire the `vistor()->visit()` method and updates the Session's `visit` key.

## Motivation and context

Why is this change required? What problem does it solve?

- The bug causing an increase in database records with every refresh is resulting in inaccurate data on total pageviews.
- This undermines the integrity of the data we collect and compromises the reliability of our analytics and reporting. As a business or website owner, having accurate and reliable visitor data is essential for making informed decisions, understanding user behavior, and optimizing our website's performance.
- The erroneous increase in database records could lead to unnecessary resource consumption, potentially affecting the system's performance and increasing operational costs. By addressing and resolving this bug, we can ensure the efficient use of our resources and maintain a streamlined data logging process.

## How has this been tested?

This change has been tested with;

* Laravel 10
* PHP 8.1
* Laravel Telescope
* Laravel Debuggar

## Screenshots (if appropriate)

![x7xxy.png](https://s6.imgcdn.dev/x7xxy.png)

![x7OC2.png](https://s6.imgcdn.dev/x7OC2.png)

![x7Z7i.png](https://s6.imgcdn.dev/x7Z7i.png)

![x793H.png](https://s6.imgcdn.dev/x793H.png)

![x7VwS.png](https://s6.imgcdn.dev/x7VwS.png)

![x7fUC.png](https://s6.imgcdn.dev/x7fUC.png)

## Types of changes

What types of changes does your code introduce? Put an `x` in all the boxes that apply:
- [x] Bug fix (non-breaking change which fixes an issue)
- [ ] New feature (non-breaking change which adds functionality)
- [ ] Breaking change (fix or feature that would cause existing functionality to change)

## Checklist:

Go over all the following points, and put an `x` in all the boxes that apply.

Please, please, please, don't send your pull request until all of the boxes are ticked. Once your pull request is created, it will trigger a build on our [continuous integration](http://www.phptherightway.com/#continuous-integration) server to make sure your [tests and code style pass](https://help.github.com/articles/about-required-status-checks/).

- [x] I have read the **[CONTRIBUTING](CONTRIBUTING.md)** document.
- [x] My pull request addresses exactly one patch/feature.
- [x] I have created a branch for this patch/feature.
- [x] Each individual commit in the pull request is meaningful.
- [x] I have added tests to cover my changes.
- [x] If my change requires a change to the documentation, I have updated it accordingly.

If you're unsure about any of these, don't hesitate to ask. We're here to help!
