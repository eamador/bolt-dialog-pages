# Bolt-dialog-pages
Bolt module that allows you to open content type "pages" within a dialog on a button click event.

## Installation
### Via composer
```sh
$ composer require eamador/bolt-dialog-pages
```

### Clone
```sh
    # from a bolt project root path
    $ cd extensions
    # only if directory does not exist already
    $ mkdir locals 
    # clone the repository
    $ git clone git@github.com:eamador/bolt-dialog-pages.git
```

### Install extension in Bolt backend
 - Go to http://your-bolt-url.dev/bolt/extend
 - In the right side, find Maintenance section, open the dropdown and click on "Install all packages" 

## Configuration
Manage the dialog-pages buttons from http://your-bolt-url.dev/bolt/extend/dialog-pages
Click on the "New" link, you will see a small form with:
- A dropdown where your existing contentypes pages will appear, choose the desire one
- Input text, this will appear as the button label.
- Add it in your template, wherever you want to display all the buttons (for example in _header.twig):
```twig
   {{ get_dialog_buttons() | raw }}
```

