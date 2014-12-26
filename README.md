Wwwision.Neos.MailChimp
=======================

TYPO3 Flow package that integrates [MailChimp](http://mailchimp.com/)® to your TYPO3 Neos site.

Features
--------

This package comes with two main features:

1. A *MailChimp® subscription finisher* for the [TYPO3 Flow Form Framework](http://flow.typo3.org/documentation/guides/forms)
2. A simple TYPO3 Neos module that allows administrators to manage MailChimp® lists and recipients

Usage
-----

Install this package and make sure to resolve all dependencies (This requires the official [PHP API client](https://packagist.org/packages/mailchimp/mailchimp).
The easiest way to install this package is to add
```json
"wwwision/neos-mailchimp": "~1.0"
```
To your Site package (or whichever package that uses the module or service)

After successful installation make sure to configure the MailChimp® API key in the `Settings.yaml`of your Site package:

```yaml
Wwwision:
  Neos:
    MailChimp:
      apiKey: '<VALID_MAILCHIMP_API_KEY>'
```

Done. You can now log in to the TYPO3 Neos backend (as administrator) and manage your newsletter lists and recipients in the new Module *administration/mailchimp* (Make sure to flush the browser caches if the module should not appear in the menu).

TYPO3 Neos Module
-----------------

The module is pretty simple and self-explanatory. Currently it allows for:

1. Displaying all lists
2. Displaying details of single lists including creation date, sender information, number of recipients
3. Displaying all members of a selected list
4. Removing members from a list
5. Subscribing new members to a list

![Screenshot of the lists module](/Module_Lists.png "TYPO3 Neos module for managing MailChimp® lists")
![Screenshot of the members](/Module_Members.png "TYPO3 Neos module for managing MailChimp® members")

Form Finisher
-------------

This package also comes with a simple form finisher that allows for creation of simple Newsletter subscription forms using the *TYPO3 Flow Form Framework*.
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
                identifier: FNAME
                label: 'First name'
                validators:
                    -
                        identifier: 'TYPO3.Flow:NotEmpty'
                properties:
                    placeholder: 'Your first name'
                defaultValue: ''
            -
                type: 'TYPO3.Form:SingleLineText'
                identifier: LNAME
                label: 'Last name'
                validators:
                    -
                        identifier: 'TYPO3.Flow:NotEmpty'
                properties:
                    placeholder: 'Your last name'
                defaultValue: ''
            -
                type: 'TYPO3.Form:SingleLineText'
                identifier: email
                label: E-Mail
                validators:
                    -
                        identifier: 'TYPO3.Flow:NotEmpty'
                    -
                        identifier: 'TYPO3.Flow:EmailAddress'
                properties:
                    placeholder: 'Your email address'
                defaultValue: ''
finishers:
    -
        identifier: 'Wwwision.Neos.MailChimp:MailChimpSubscriptionFinisher'
        options:
            listId: 'd2a96c360a'
    -
        identifier: 'TYPO3.Form:Confirmation'
        options:
            message: 'Thank you, your subscription was successful. Please check your email.'
renderingOptions:
    submitButtonLabel: '
```

*Note:* The Form finisher can of course be used without TYPO3 Neos (i.e. for Newsletter-subscriptions within plain TYPO3 Flow applications).

Worth knowing
-------------

This package demonstrates...

...how to reuse TYPO3 Neos layouts and partials with [Views.yaml](http://docs.typo3.org/flow/TYPO3FlowDocumentation/TheDefinitiveGuide/PartIII/ModelViewController.html#configuring-views-through-views-yaml)

...how to create & configure a form finishers so that it can be used in the FormBuilder

...how to make use of [Objects.yaml](http://docs.typo3.org/flow/TYPO3FlowDocumentation/TheDefinitiveGuide/PartIII/ObjectManagement.html#sect-configuring-objects) to initialize custom API clients

License
-------

Licensed under GPLv3+, see [LICENSE](LICENSE)