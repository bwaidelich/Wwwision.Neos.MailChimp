Wwwision.Neos.MailChimp
=======================

Package that integrates [MailChimp](http://mailchimp.com/)® to your [Neos](https://www.neos.io) site or [Flow]((https://flow.neos.io) application.

Features
--------

This package comes with two main features:

1. A *MailChimp® subscription finisher* for the [Flow Form Framework](https://flow-form-framework.readthedocs.io/en/stable/)
2. A simple *Neos* module that allows Neos administrators to manage MailChimp® lists and recipients

Usage
-----

Install this package and make sure to resolve all dependencies (This requires the official [PHP API client](https://packagist.org/packages/mailchimp/mailchimp).
The easiest way to install this package is to add
```json
"wwwision/neos-mailchimp": "^2.1"
```
To your Site package (or whichever package that uses the module or service) and install it and it's dependencies via `composer install`.

**Note:** If you get an error `Class 'mailchimp' not found`, try to optimize the composer autoloading classmap with `composer install --optimize-autoloader`. That should resolve the issue.

After successful installation make sure to configure the MailChimp® API key in the `Settings.yaml`of your Site package:

```yaml
Wwwision:
  Neos:
    MailChimp:
      apiKey: '<VALID_MAILCHIMP_API_KEY>'
```

**Note:** The API key can be obtained from `mailchimp.com > Account > Extras > API Key`

Done. You can now log-in to the Neos backend (as administrator) and manage your newsletter lists and recipients in the new Module `administration/mailchimp` (Make sure to flush the browser caches if the module should not appear in the menu).

Neos Module
-----------

The module is pretty simple and self-explanatory. Currently it allows for:

1. Displaying all lists
2. Displaying details of single lists including creation date, sender information, number of recipients
3. Displaying all members of a selected list
4. Removing members from a list
5. Subscribing new members to a list

![Screenshot of the lists module](/Module_Lists.png "Neos module for managing MailChimp® lists")
![Screenshot of the members](/Module_Members.png "Neos module for managing MailChimp® members")

Form Finisher
-------------

This package also comes with a simple form finisher that allows for creation of simple Newsletter subscription forms using the *Flow Form Framework*.
It also adds the corresponding *FormBuilder* configuration so that the finisher can be used directly in the visual editor.

Alternatively you can save the following snippet to `Data/Forms/newsletter.yaml` to create a simple newsletter subscription form:

```yaml
type: 'TYPO3.Form:Form'
identifier: mailchimp
label: Mailchimp
renderables:
    -
        type: 'TYPO3.Form:Page'
        identifier: page1
        label: 'Page 1'
        renderables:
            -
                type: 'TYPO3.Form:SingleLineText'
                identifier: 'firstName'
                label: 'First name'
                validators:
                    -
                        identifier: 'TYPO3.Flow:NotEmpty'
                properties:
                    placeholder: 'Your first name'
                defaultValue: ''
            -
                type: 'TYPO3.Form:SingleLineText'
                identifier: 'lastName'
                label: 'Last name'
                validators:
                    -
                        identifier: 'TYPO3.Flow:NotEmpty'
                properties:
                    placeholder: 'Your last name'
                defaultValue: ''
            -
                type: 'TYPO3.Form:SingleLineText'
                identifier: 'email'
                label: 'E-Mail'
                validators:
                    -
                        identifier: 'TYPO3.Flow:NotEmpty'
                    -
                        identifier: 'TYPO3.Flow:EmailAddress'
                    -
                        identifier: 'Wwwision.Neos.MailChimp:UniqueSubscription'
                        options:
                          listId: '<MAILCHIMP-LIST-ID>'
                properties:
                    placeholder: 'Your email address'
                defaultValue: ''
finishers:
    -
        identifier: 'Wwwision.Neos.MailChimp:MailChimpSubscriptionFinisher'
        options:
            listId: '<MAILCHIMP-LIST-ID>'
            additionalFields:
              'FNAME': '{firstName}'
              'LNAME': '{lastName}'
    -
        identifier: 'TYPO3.Form:Confirmation'
        options:
            message: 'Thank you, your subscription was successful. Please check your email.'
renderingOptions:
    submitButtonLabel: ''
```

**Note:** Replace "\<MAILCHIMP-LIST-ID\>" with a valid list identifier that can be obtained from `mailchimp.com > Lists > <YOUR-LIST> > Settings > List name & defaults`. A list ID usually contains letters and numbers such as "d2a96c360f".

The Form finisher can of course be used without Neos (i.e. for Newsletter-subscriptions within plain Flow applications).

Trivia
------

This package demonstrates...

...how to reuse Neos layouts and partials with [Views.yaml](http://flowframework.readthedocs.io/en/stable/TheDefinitiveGuide/PartIII/ModelViewController.html#configuring-views-through-views-yaml)

...how to create & configure a form finishers so that it can be used in the FormBuilder

...how to make use of [Objects.yaml](http://flowframework.readthedocs.io/en/stable/TheDefinitiveGuide/PartIII/ObjectManagement.html#configuring-objects) to initialize custom API clients

...how to make arbitrary result sets coubtable and "paginatable" using a `CallbackQueryResult` object

License
-------

Licensed under GPLv3+, see [LICENSE](LICENSE)
