This tool converts county-specific election result PDF documents into JSON format for easy importing.

## Install

Install PHP 7.x (http://php.net/manual/en/install.php)

Install composer (https://getcomposer.org/doc/00-intro.md)

Run `composer install` to install required dependancies.

## Run

To convert a PDF file, place the PDF file into the proper county folder.
The first PDF file found in the folder will be converted.

Run the following command to convert from PDF to JSON

`php converter.php <county name>`


The JSON data is saved with the same filename as the original PDF but with a .json extension.
 
## Create new Converters

Create a new directory within the existing `Counties` directory and name it for the new county you are building a converter for.

A converter must implement a parsePageText method and return a structured array built from that text, however since most reports follow a common format a ConverterTemplate is available to simplify individual converters.

Most election result PDF documents breakdown into a `Pages`->`Races`->`Choices` relationship, 
Each page contains some number of races, and each race contains some number of choices.

To support this common model, the ConverterTemplate contains helper methods so that the only methods that need to be implemented by a new converter are the parser methods for a Page, a set of Races, and a set of Choices. Each parser uses a Regular Expression pattern to extract the relevant data points and map them into a defined structure, before passing the contained text down to the next parser.

Add the new converter to the ConverterFactory so it can be executed with the main converter.php script.

When building RegEx rules, I recommend using a tool (like RegEx101.com) to help visualize how the string is being matched as you build it. If you make a mistake it's much easier to catch and fix as you go, rather than trying to debug why a fully completed pattern doesn't match.
