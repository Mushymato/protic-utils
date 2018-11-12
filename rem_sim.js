var REMRate = [];
var totalRate = 0;
function generateRoll(){
	var randNum = Math.random() * totalRate;
	var weightSum = 0;
	 
	for (var i = 0; i < REMRate.length; i++) {
		weightSum += REMRate[i]['rate'];
		weightSum = +weightSum.toFixed(2);
		 
		if (randNum <= weightSum) {
			return i;
		}
	}
	return false;
}
function rollREM(){
	document.getElementById('roll-rem').style.display = 'none';
	document.getElementById('rem-box').style.backgroundImage = 'url("./assets/BG_1.PNG")';
	document.getElementById('roll-result').setAttribute('src', '');
	var idx = generateRoll();
	var card_id = REMRate[idx]['id'];
	var card_egg = REMRate[idx]['egg'];
	document.getElementById('rem-egg-icon').setAttribute('src', 'https://pad.protic.site/wp-content/uploads/pad-eggs/' + card_egg + '.png');
	document.getElementById('rem-egg-flash').classList.add('flash');
	setTimeout(function(){
		document.getElementById('roll-result').setAttribute('src', 'https://storage.googleapis.com/mirubot/padimages/jp/full/' + card_id + '.png');		
		var icon = document.createElement('img');
		icon.setAttribute('src', 'https://storage.googleapis.com/mirubot/padimages/jp/portrait/' + card_id + '.png');
		var resList = document.getElementById('roll-result-list');
		resList.appendChild(icon);
		resList.scrollTop = resList.scrollHeight;
		
		document.getElementById('rem-egg-icon').setAttribute('src', '');
	}, 1800);
	setTimeout(function(){
		document.getElementById('rem-egg-flash').classList.remove('flash');
	}, 2000);
	
	document.getElementById('roll-rem').style.display = 'block';
}
window.onload=function(){
	function loadREMRates(data){
		var url = new URL(window.location.href);
		var currentREM = url.searchParams.get('rem');

		var fullNames = data['FullNames'];
		var selectREM = document.getElementById('rem-select');
		var option = document.createElement('option');
		selectREM.appendChild(option);
		for(var name in fullNames){
			if (fullNames.hasOwnProperty(name)) {
				option = document.createElement('option');
				option.setAttribute('value', name);
				option.innerHTML = fullNames[name];
				if(name === currentREM){
					option.selected = true;
				}
				selectREM.appendChild(option);
			}
		}
		var rem = data[currentREM];
		for(var rarity of rem){
			for(var id of rarity['id_array']){
				REMRate.push({'id' : id, 'rate' : rarity['rate'], 'egg' : rarity['egg']});
				totalRate += rarity['rate'];
			}
		}
	}
	$.getJSON('./rem_rates.json', loadREMRates);
	document.getElementById('roll-rem').addEventListener('click', rollREM);
}