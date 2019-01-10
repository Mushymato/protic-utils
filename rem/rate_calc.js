function sumRates(){
	var machines = document.querySelectorAll("form[id^='rem-']");
	for(var rem of machines){
		var sumSelected = 0;
		var remID = rem.getAttribute("id");
		var rateGroups = rem.querySelectorAll("#"+remID+" div.rate-group");
		for(var rg of rateGroups){
			var rate = parseFloat(rg.getAttribute("data-rate"));
			var padCheckbox = rg.querySelectorAll("#"+remID+" input[type='checkbox'].rem-icon-cb");
			for(var cb of padCheckbox){
				if(cb.checked){
					sumSelected += rate;
				}
			}
		}
		rem.querySelector("#"+remID+" span.total-rate").innerHTML = Number(sumSelected).toFixed(2);
	}
}
window.onload=function(){
	var padCheckbox = document.querySelectorAll('input[type="checkbox"].rem-icon-cb');
	for(var cb of padCheckbox){
		cb.addEventListener("change", sumRates);
	}
	var clearBtns = document.getElementsByClassName("clear-selected");
	for(var btn of clearBtns){
		btn.addEventListener("click", 
			function(){
				document.querySelector("#"+event.srcElement.getAttribute("data-machineid")+" span.total-rate").innerHTML = "0.00";
			}
		);
	}
	var tabLinks = document.getElementsByClassName("egg-machine-tab-link");
	for(var link of tabLinks){
		link.addEventListener("click", 
			function(){
				var target = event.srcElement.getAttribute("data-machineid");
				var machines = document.querySelectorAll("form[id*='em-']");
				for(var machine of machines){
					if(machine.getAttribute("id") === target){
						machine.style.opacity = "1";
						machine.style.visibility = "visible";
					}else{
						machine.style.opacity = "0";
						machine.style.visibility = "hidden";
					}
				}
			}
		);
	}
	tabLinks[0].click();
}