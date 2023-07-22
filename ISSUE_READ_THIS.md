## Detailed description

![x72F8.png](https://s6.imgcdn.dev/x72F8.png)

### Bug: Multiple Database Entries on Refresh

Middleware Log Visits automatically, but does not prevent from duplicate or multiple visits entries into database.

When user refreshes the `vistor()->visit()` is fired everytime, resulting in increase in database request and log every time.

## Context

#### Why is this change important to you?

- The bug causing an increase in database records with every refresh is resulting in inaccurate data on total pageviews.
- This undermines the integrity of the data we collect and compromises the reliability of our analytics and reporting. As a business or website owner, having accurate and reliable visitor data is essential for making informed decisions, understanding user behavior, and optimizing our website's performance.
- The erroneous increase in database records could lead to unnecessary resource consumption, potentially affecting the system's performance and increasing operational costs. By addressing and resolving this bug, we can ensure the efficient use of our resources and maintain a streamlined data logging process.

#### How can it benefit other users?

- With accurate data on visitor behavior, website owners can personalize content, improve navigation, and address pain points, ultimately leading to a more satisfying and relevant user experience.

- For businesses that share visitor data with partners or third-party services, having accurate data establishes a strong foundation for collaboration and fosters healthy partnerships.

- It will reduce the amount of unwanted database queries made.

## Implementation

When user hits the route protected with &nbsp; `LogVisits` &nbsp; middleware, it will first check in &nbsp; `Session`&nbsp; for 'visit' key which contains &nbsp; `current $request IP and URL.` &nbsp;

![x7xxy.png](https://s6.imgcdn.dev/x7xxy.png)

If key doest not exist, it will store the current request IP and URL into visit key array, then fires the &nbsp; `visitor()->visit()` &nbsp; which will log the record into database.

If Key Exists, then it will check the previously stored IP and URL are same or not, if both are same, then visit() method won't be fired. If any of them is different, &nbsp; `visitor()->visit()` &nbsp; will be fired, and session data will be updated with new values.

![x7OC2.png](https://s6.imgcdn.dev/x7OC2.png)

![x7Z7i.png](https://s6.imgcdn.dev/x7Z7i.png)

![x793H.png](https://s6.imgcdn.dev/x793H.png)

![x7VwS.png](https://s6.imgcdn.dev/x7VwS.png)

![x7fUC.png](https://s6.imgcdn.dev/x7fUC.png)

**Note:** Only Middleware is protected with session, the `visitor()->visit()` is not, if user call this method directly from controller, then duplicates will be created on every request.;

Update the readme file accordingly encouraging to use Middleware directly to route instead of calling `visitor()->visit()`, or modify `visit()` method same as Middleware.

## Your environment

Include as many relevant details about the environment you experienced the bug in and how to reproduce it.

* PHP 8.1
* Laravel 10 for package testing.

