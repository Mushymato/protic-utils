window.onload=function(){
	var REMRate = [];
	var totalRate = 0;
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
	function rand(min, max) {
		return Math.random() * (max - min) + min;
	};
	function rollREM(){
		var randNum = rand(0, totalRate);
		var weightSum = 0;
		 
		for (var i = 0; i < REMRate.length; i++) {
			weightSum += REMRate[i]['rate'];
			weightSum = +weightSum.toFixed(2);
			 
			if (randNum <= weightSum) {
				document.getElementById('roll-result').setAttribute('src', 'https://storage.googleapis.com/mirubot/padimages/jp/full/' + REMRate[i]['id'] + '.png');
				
				var icon = document.createElement('img');
				icon.setAttribute('src', 'https://storage.googleapis.com/mirubot/padimages/jp/portrait/' + REMRate[i]['id'] + '.png');
				document.getElementById('roll-result-list').appendChild(icon);
				
				break;
			}
		}
	}
	$.getJSON('./rem_rates.json', loadREMRates);
	document.getElementById('roll-button').addEventListener('click', rollREM);
}