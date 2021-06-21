# Export Joomla Db Simple version
Export joomla database

Using for export database from joomla. Use query string `tbl` to export specific tables.

Eg: 
- if you want to export `prefix_content`, just use table name without prefix. `tbl=content`
- if you want to export multiple tables, use `,` to separeate. `tbl=content,extensions`

`https://localhost?tbl=content,extensions`
