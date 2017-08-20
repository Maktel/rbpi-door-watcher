fetch('server_grove.php')
  .then(
  function (response) {
    if (response.status !== 200) {
      console.log('Looks like there was a problem. Status Code: ' +
        response.status);
      return;
    }

    // Examine the text in the response  
    response.json().then(function (data) {
      processData(data);
    });
  }
  )
  .catch(function (err) {
    console.log('Fetch Error :-S', err);
  });

const processData = (json) => {
  let table = document.createElement('table');
  let tbody = document.createElement('tbody');

  {
    let tr = document.createElement('tr');
    let th_timestamp = document.createElement('th');
    th_timestamp.appendChild(document.createTextNode('Time'));

    let th_temperature = document.createElement('th');
    th_temperature.appendChild(document.createTextNode('Temp'));

    let th_light = document.createElement('th');
    th_light.appendChild(document.createTextNode('Light'));

    let th_sound = document.createElement('th');
    th_sound.appendChild(document.createTextNode('Sound'));

    let th_doors = document.createElement('th');
    th_doors.appendChild(document.createTextNode('Doors'));

    tr.appendChild(th_timestamp);
    tr.appendChild(th_doors);
    tr.appendChild(th_temperature);
    tr.appendChild(th_light);
    tr.appendChild(th_sound);

    tbody.appendChild(tr);
  }

  for (let obj of json) {
    let tr = document.createElement('tr');

    let timestamp = getDateString(obj.meta.timestamp);
    let td_timestamp = document.createElement('td');
    td_timestamp.appendChild(document.createTextNode(timestamp));

    let temperature = '' + Math.abs(parseInt(obj.data.temp));
    let td_temperature = document.createElement('td');
    td_temperature.appendChild(document.createTextNode(temperature));

    let light = (obj.data.light).toFixed(2) + '%';
    let td_light = document.createElement('td');
    td_light.appendChild(document.createTextNode(light));

    let sound = (obj.data.sound).toFixed(2) + '%';
    let td_sound = document.createElement('td');
    td_sound.appendChild(document.createTextNode(sound));

    let dist1 = obj.data.dist;
    let dist2 = obj.data.dist2;

    let td_doors = document.createElement('td');
    let isClosed = 'Closed';
    if ((dist1 < 100 || dist1 > 150) && (dist2 < 100 || dist2 > 150)) {
      // doors open
      isClosed = 'Open';
      td_doors.classList.add('doorsOpen');
    } else {
      td_doors.classList.add('doorsClosed');
    }
    td_doors.appendChild(document.createTextNode(isClosed));

    tr.appendChild(td_timestamp);
    tr.appendChild(td_doors);
    tr.appendChild(td_temperature);
    tr.appendChild(td_light);
    tr.appendChild(td_sound);

    tbody.appendChild(tr);
  }

  table.appendChild(tbody);
  document.body.appendChild(table);
}

const getDateString = (t) => {
  const options = { weekday: 'narrow', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit' };
  let date = new Date(t * 1000);

  return date.toLocaleDateString("pl-PL", options);
}
