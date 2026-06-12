<?php
// Only show on localhost for safety during development
if ($_SERVER['HTTP_HOST'] === 'localhost' || $_SERVER['HTTP_HOST'] === '127.0.0.1') {
?>
<button type="button" class="btn btn-warning dev-only-fill-btn m-r-10" onclick="fillRandomData()">Fill Random Data</button>
<script>
function fillRandomData() {
    const randomString = (prefix) => prefix + Math.floor(Math.random() * 10000);
    const randomNumber = (length) => Math.floor(Math.random() * Math.pow(10, length)).toString().padStart(length, '0');

    document.getElementById("FirstName").value = randomString("John");
    document.getElementById("LastName").value = randomString("Doe");
    document.getElementById("Company").value = randomString("Corp ");
    document.getElementById("Designation").value = "Manager";
    document.getElementById("Nationality").value = "Indian";
    
    document.getElementById("Gmail").value = randomString("user") + "@example.com";
    
    document.getElementById("PincodeOfArea").value = "Area " + randomNumber(2);
    document.getElementById("Pincode").value = "4110" + randomNumber(2);
    document.getElementById("state").value = "Maharashtra";
    document.getElementById("City").value = "Pune";
    
    document.getElementById("mob1").value = "9" + randomNumber(9);
    if(document.getElementById("offNum")) document.getElementById("offNum").value = "2" + randomNumber(7);
    if(document.getElementById("Contact2")) document.getElementById("Contact2").value = "8" + randomNumber(9);
    if(document.getElementById("Contact3")) document.getElementById("Contact3").value = "7" + randomNumber(9);
    
    if(document.getElementById("Facebook")) document.getElementById("Facebook").value = randomString("fb_user");
    if(document.getElementById("Relationship")) document.getElementById("Relationship").value = "Single";
    
    let countrySelect = document.getElementById("Country");
    if(countrySelect && countrySelect.options.length > 1) {
        countrySelect.selectedIndex = 1;
    }
}
</script>
<?php
}
?>
