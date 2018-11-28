This tool converts county-specific PDF documents into JSON format for easy importing.

##Install

Install PHP 7.x (http://php.net/manual/en/install.php)

Install composer (https://getcomposer.org/doc/00-intro.md)

Run `composer install` to install required dependancies.

##Run

To convert a PDF file, place the PDF file into the proper county folder.
The first PDF file found in the folder will be converted.

Run the following command to convert from PDF to JSON

`php converter.php <county name>`


The JSON data is saved with the same filename as the original PDF but with a .json extension.
 
##Create new Converters

Create a new folder and copy an existing Converter.php file into the folder.

Implement a parsePageText function that parses the text returned from the PDF document into JSON data. 
Usually this is done by building long Regular Expressions based on the formatting of the document.

Add the new converter to the ConverterFactory so it can be executed with the main converter.php script.

When building RegEx rules, I recommend copying the section you are currently working on into 
RegEx101.com and slowly building your parsing rules up there. If you make a mistake you should notice it instantly, 
rather than trying to debug why it simply doesn't match.

Most PDF documents breakdown into a Pages->Races->Choices relationship. 
Each page has some number of races on it, and each race has some number of choices in it. 
This distinction is important to properly handle the cases when the number of choices per race (or races per page) changes.

Start by pasting the entire parsed page into RegEx101.com and try to build a rule that anchors on the page headers and footers.
It should extract any relevant data from the headers and footers, and all of the body data as a new string.

The body content is passed to the next parser that parses out the data about each race, as well as a new string containing all the choices.

Finally, the choice string is parsed to get relevant data about each choice.

All the parsed data is returned as a single JSON object that should represents all the data and relationships that were visible in the original PDF report.

