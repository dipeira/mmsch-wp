### MM sch API Table wordpress plugin

Fetch data from mm.sch.gr API and display it in a sortable, searchable table using DataTables.net.

Usage:
1. Upload plugin to WordPress
2. Setup plugin at Settings -> MM API Table:
- "Διεύθυνση Εκπαίδευσης": Enter the ID or the name, see [here](https://mm.sch.gr/docs/function-GetEduAdmins.html)
- "Όνομα χρήστη ΠΣΔ", "Κωδικός χρήστη ΠΣΔ": Enter the sch.gr credentials for authorized access to MM
3. Enter shortcode `api_table` on a page or a post.
- Parameters: pairs="[column_name1]-[property_name1], [column_name2]-[property_name2]"
Property names can be found [here](https://mm.sch.gr/docs/function-GetUnits.html)

#### Example: 
[api_table pairs="Τύπος-unit_type, Όνομα-name, Ταχ.Δ/νση-street_address, Τηλ-phone_number, Email-email, Δήμος-municipality"]