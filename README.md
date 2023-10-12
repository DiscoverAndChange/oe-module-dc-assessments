# oe-module-dc-assessments

This [OpenEMR Module](https://www.open-emr.org/) is designed to provide Surveys/Assessments/Questionnaire enhancements to the OpenEMR project.

It has the following features:
- Assessment Platform - create, assign, and view reports of patient tasks(homework,assessments, etc), receive email notifications when patients have completed assignments.
- New FHIR endpoints (Questionnaire, QuestionnaireResponse, Task)
- Calendar Enhancements - assign patient documents
- Encounter Screen - See completed patient assessments, questionnaire responses.
- Resource importer (100+ CBT assignments/articles, 40+ assessments available from www.discoverandchange.com)
- Fax/SMS integration

## Getting Started
You can get started by installing the module and then activating it from the OpenEMR modules page.


### Installing Module Via Composer
There are two ways to install your module via composer.  
```
composer require DiscoverAndChange/oe-module-dc-assessments
```

### Installing Module via filesystem
Download the zip file for the module from github and extract the zip into 
 *<openemr_installation_directory>/interface/modules/custom_modules/*

### Installing Module from github
You can start by cloning the project inside the OpenEMR custom modules location.  
This is at 
```git
git clone https://github.com/DiscoverAndChange/oe-module-dc-assessments <your-project-name>
```
Update the composer.json file properties for your own project.

### Activating Your Module
Install your module using either composer (recommended) or by placing your module in the *<openemr_installation_directory>//interface/modules/custom_modules/*.

Once your module is installed in OpenEMR custom_modules folder you can activate your module in OpenEMR by doing the following.

  1. Login to your OpenEMR installation as an administrator
  2. Go to your menu and select Modules -> Manage Modules
  3. Click on the Unregistered tab in your modules list
  4. Find Discover And Change Assessments module and click the *Register* button.  This will reload the page and put the module in the Registered list tab of your modules
  5. Now click the *Install* button next to the module name.
  6. Finally click the *Enable* button for the module.
  7. Logout and log back in again.
  8. Verify the module is working by going to Miscellaneous -> Patient Portal Assignments.

### Using the Module
Coming Soon

## Contributing
If you would like to help in improving the skeleton library just post an issue on Github or send a pull request.
