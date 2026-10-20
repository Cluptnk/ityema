var languageDictionary = []; // create an empty array

if (localStorage.getItem("ITEMAlanguage") !== null) {
  console.log("---> Get language from ls");
  languageString = localStorage.getItem("ITEMAlanguage");
  var arr = JSON.parse(languageString);
  for (i = 0; i < arr.length; i++) {
    languageDictionary[arr[i].CodePhrase] = arr[i].Frase;
  }
} else {
  fillLanguageDictionaryAsync().then((languageString) => {
    localStorage.setItem("ITEMAlanguage", languageString);
    var arr = JSON.parse(languageString);
    for (i = 0; i < arr.length; i++) {
      languageDictionary[arr[i].CodePhrase] = arr[i].Frase;
    }
  });
}

function fillLanguageDictionaryAsync() {
  console.log("---> Set language to ls");
  return new Promise((resolve, reject) => {
    $.ajax({
      type: "POST",
      url: "./JSONmsg.php",
      data: {
        type: 0,
      },
      success: (response) => {
        resolve(response);
      },
      error: (error) => {
        console.log({
          error,
        });
        reject(error);
      },
    });
  });
}

//type=5
function getEventi_list(filters, bLoomId = false) {
  waitingDialog.show();

  var thead = "";
  var xmlhttp = new XMLHttpRequest();
  var url = "./JSONmsg.php";
  xmlhttp.onreadystatechange = function () {
    if (xmlhttp.status === 200) {
      getMsgthdead(xmlhttp.responseText);
    }
  };
  xmlhttp.open("POST", url, false);
  xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
  xmlhttp.send("type=27");

  function getMsgthdead(response) {
    var arr = JSON.parse(response);
    thead += "<tr>";
    if (document.getElementById("checkVisualizzaOrario").checked === true)
      thead += "<th><center>" + arr[6].Valore + "</center></th>";
    if (document.getElementById("checkVisualizzaIDTelaio").checked === true)
      thead += "<th><center>" + arr[0].Valore + "</center></th>";
    if (document.getElementById("checkVisualizzaNomeTelaio").checked === true)
      thead += "<th><center>" + arr[1].Valore + "</center></th>";
    // if (document.getElementById("checkVisualizzaGruppo").checked === true)
    //   thead += "<th><center>" + arr[2].Valore + "</center></th>";
    // if (document.getElementById("checkVisualizzaSala").checked === true)
    //   thead += "<th><center>" + arr[3].Valore + "</center></th>";
    if (document.getElementById("checkVisualizzaEvento").checked === true)
      thead += "<th><center>" + arr[4].Valore + "</center></th>";
    if (
      document.getElementById("checkVisualizzaStatoProduzione").checked === true
    )
      thead += "<th><center>" + languageDictionary["eventi"] + "</center></th>"; //Eventi al posto di Stato Produzione
    // if (
    //   document.getElementById("checkVisualizzaDichiarazioneFermata").checked ===
    //   true
    // )
    //   thead += "<th><center>" + arr[7].Valore + "</center></th>";
    if (document.getElementById("checkVisualizzaTurno").checked === true)
      thead += "<th><center>" + arr[8].Valore + "</center></th>";
    if (document.getElementById("checkVisualizzaFar").checked === true)
      thead += "<th><center>" + arr[9].Valore + "</center></th>";
    if (document.getElementById("checkVisualizzaRFID").checked === true)
      thead += "<th><center>" + arr[10].Valore + "</center></th>";
    if (
      document.getElementById("checkVisualizzaLungProdDaIniz").checked === true
    )
      thead +=
        "<th><center>" +
        arr[11].Valore +
        " [" +
        arr[21].Valore +
        "]</center></th>";
    if (document.getElementById("checkVisualizzaNumeroPezza").checked === true)
      thead += "<th><center>" + arr[12].Valore + "</center></th>";
    if (document.getElementById("checkVisualizzaTempoEvento").checked === true)
      thead += "<th><center>" + arr[13].Valore + "</center></th>";
    if (document.getElementById("checkVisualizzaNomeArticolo").checked === true)
      thead += "<th><center>" + arr[14].Valore + "</center></th>";
    if (
      document.getElementById("checkVisualizzaNomeArmatura1").checked === true
    )
      thead += "<th><center>" + arr[15].Valore + "</center></th>";
    if (
      document.getElementById("checkVisualizzaNomeArmatura2").checked === true
    )
      thead += "<th><center>" + arr[16].Valore + "</center></th>";
    if (document.getElementById("checkVisualizzaNomeDisegno").checked === true)
      thead += "<th><center>" + arr[17].Valore + "</center></th>";
    if (document.getElementById("checkVisualizzaLottoFilatoA").checked === true)
      thead +=
        "<th><center>" + languageDictionary["LottoOrditoA"] + "</center></th>";
    if (document.getElementById("checkVisualizzaLottoFilatoB").checked === true)
      thead +=
        "<th><center>" + languageDictionary["LottoOrditoB"] + "</center></th>";
    if (
      document.getElementById("checkVisualizzaLottoFilatoTrama").checked ===
      true
    )
      thead +=
        "<th><center>" + languageDictionary["LottoTrama"] + "</center></th>";
    thead += "</tr>";
    // document.getElementById("dataTables-eventi-thead").innerHTML = thead;
  }

  var xmlhttp = new XMLHttpRequest();
  var url = "./JSONmsg.php";
  xmlhttp.onreadystatechange = function () {
    if (xmlhttp.readyState === 4 && xmlhttp.status === 200) {
      getMsg(xmlhttp.responseText);
      waitingDialog.hide();
    }
  };
  var append = "";
  // selezione idTelaio es &id=99
  selectedOptions = $("#telaiList option:selected");
  if (selectedOptions.length > 0) {
    var primo = true;
    for (var i = 0; i < selectedOptions.length; i++) {
      if (primo) {
        append += "&id=" + selectedOptions[i].value;
      } else {
        append += "," + selectedOptions[i].value;
      }
      primo = false;
    }
  }

  //selezione statoProduzione
  var selectedOptions;
  selectedOptions = $("#causeEventiList option:selected");
  if (selectedOptions.length > 0) {
    var primo = true;
    var primoAgg = true;
    for (var i = 0; i < selectedOptions.length; i++) {
      if (selectedOptions[i].value < 256 && selectedOptions[i].value <= 20) {
        //<=20 per merge cause e trigger nello stesso menu
        if (primoAgg) {
          append += "&spAgg=" + selectedOptions[i].value;
        } else {
          append += "," + selectedOptions[i].value;
        }
        primoAgg = false;
      }
      if (selectedOptions[i].value >= 256) {
        if (primo) {
          append += "&sp=" + selectedOptions[i].value;
        } else {
          append += "," + selectedOptions[i].value;
        }
        primo = false;
      }
    }
  }

  //cambi ovvero gli oggetti con value >20 e <256 selezionati dopo il merge delle due multiselect in una
  selectedOptions = $("#causeEventiList option:selected");
  if (selectedOptions.length > 0) {
    var primo = true;
    for (var i = 0; i < selectedOptions.length; i++) {
      if (selectedOptions[i].value > 20 && selectedOptions[i].value < 256) {
        //è un trigger
        if (primo) {
          append += "&trig=" + (selectedOptions[i].value - 20);
        } else {
          append += "," + (selectedOptions[i].value - 20);
        }
        primo = false;
      }
    }
  }

  if (
    $("#datetimepicker6").data("date") !== undefined &&
    $("#datetimepicker7").data("date") !== undefined
  )
    append +=
      "&from='" +
      $("#datetimepicker6").data("date") +
      "'&to='" +
      $("#datetimepicker7").data("date") +
      "'";
  if (document.getElementById("checkLimita100").checked === true)
    append += "&limit=1";
  xmlhttp.open("POST", url, true);
  xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
  xmlhttp.send("type=5" + append);

  function getMsg(response) {
    var arr = JSON.parse(response);
    let tempArr = JSON.parse(JSON.stringify(arr));
    //Setto il titolo
    if (tempArr && tempArr.length && document.getElementById("storicoTitle")) {
      document.getElementById("storicoTitle").innerHTML =
        tempArr[0].IDTelaio + " - " + tempArr[1].NomeTelaio;
    }

    //Parte di 'watch' con evidenziazione variazioni
    let objByTelaioID = tempArr.reduce(function (r, a) {
      r[a.IDTelaio] = r[a.IDTelaio] || [];
      r[a.IDTelaio].push(a);
      return r;
    }, Object.create(null));
    for (key in objByTelaioID) {
      let prevEl;
      let evalEl;
      objByTelaioID[key].reverse().forEach((el, key) => {
        if (key === 0) {
          prevEl = JSON.parse(JSON.stringify(el)); //copia per valore
          el.TempoEvento = myPrettyTimelength(prevEl.TempoEvento);
        } else {
          //Confronto evalEl vs prevEl, includendo i campi di interesse
          let triggerFields = [
            "Turno",
            "StatoProduzione",
            "Far",
            "Articolo",
            "Armatura1",
            "Armatura2",
            "Disegno",
            "LottoOrditoA",
            "LottoOrditoB",
            "LottoTrama",
            "Dichiarazione",
            "NumeroPezza",
            "TempoEvento",
          ];
          let triggerSet = new Set(triggerFields);
          evalEl = JSON.parse(JSON.stringify(el)); //copia per valore
          for (k in el) {
            if (triggerSet.has(k)) {
              if (k !== "TempoEvento") {
                el[k] =
                  evalEl[k].trim() === prevEl[k].trim()
                    ? evalEl[k]
                    : `<p style="color: red;">${evalEl[k]}</p>`;
              } else {
                evalEl[k] = myPrettyTimelength(evalEl[k]);
                el[k] = evalEl[k];
                // evalEl[k].trim() === prevEl[k].trim()
                //   ? evalEl[k]
                //   : `<p style="color: red;">${evalEl[k]}</p>`;
              }
            }
          }
          prevEl = evalEl;
        }
      });
    }
    let arrayByTelaioID = Object.keys(objByTelaioID)
      .map((key) => objByTelaioID[key])
      .flat()
      .reverse();
    //! fine 'watch'

    var msg = "";
    for (i = 0; i < arrayByTelaioID.length; i++) {
      msg += '<tr class="gradeA">';
      if (document.getElementById("checkVisualizzaOrario").checked === true)
        msg += "<td><center>" + moment(arrayByTelaioID[i].Timestamp, 'YYYY-MM-DD HH:mm:ss').format('YYYY-MM-DD HH:mm') + "</center></td>";
      if (document.getElementById("checkVisualizzaIDTelaio").checked === true)
        msg += "<td><center>" + arrayByTelaioID[i].IDTelaio + "</center></td>";
      if (document.getElementById("checkVisualizzaNomeTelaio").checked === true)
        msg +=
          "<td><center>" + arrayByTelaioID[i].NomeTelaio + "</center></td>";
      // if (document.getElementById("checkVisualizzaGruppo").checked === true)
      //   msg +=
      //     "<td><center>" + arrayByTelaioID[i].NomeGruppo + "</center></td>";
      // if (document.getElementById("checkVisualizzaSala").checked === true)
      //   msg += "<td><center>" + arrayByTelaioID[i].NomeSala + "</center></td>";
      if (document.getElementById("checkVisualizzaEvento").checked === true)
        msg += "<td><center>" + arrayByTelaioID[i].Evento + "</center></td>";
      if (
        document.getElementById("checkVisualizzaStatoProduzione").checked ===
        true
      )
        msg +=
          "<td><center>" +
          arrayByTelaioID[i].StatoProduzione +
          "</center></td>";
      // if (
      //   document.getElementById("checkVisualizzaDichiarazioneFermata")
      //     .checked === true
      // )
      //   msg +=
      //     "<td><center>" + arrayByTelaioID[i].Dichiarazione + "</center></td>";
      if (document.getElementById("checkVisualizzaTurno").checked === true)
        msg += "<td><center>" + arrayByTelaioID[i].Turno + "</center></td>";
      if (document.getElementById("checkVisualizzaFar").checked === true)
        msg += "<td><center>" + arrayByTelaioID[i].Far + "</center></td>";
      if (document.getElementById("checkVisualizzaRFID").checked === true) {
        let rfid = isIdentile(arrayByTelaioID[i].RFID, "?")
          ? "---"
          : arrayByTelaioID[i].RFID;
        msg += "<td><center>" + rfid + "</center></td>";
      }
      if (
        document.getElementById("checkVisualizzaLungProdDaIniz").checked ===
        true
      )
        msg +=
          "<td><center>" +
          parseFloat(arrayByTelaioID[i].LunghezzaDaInizio).toFixed(2) +
          " </center></td>";
      if (
        document.getElementById("checkVisualizzaNumeroPezza").checked === true
      )
        msg +=
          "<td><center>" + arrayByTelaioID[i].NumeroPezza + "</center></td>";
      if (
        document.getElementById("checkVisualizzaTempoEvento").checked === true
      )
        msg +=
          '<td data-order="' +
          Number(arr[i].TempoEvento) +
          '"><center>' +
          arrayByTelaioID[i].TempoEvento +
          "</center></td>";
      if (
        document.getElementById("checkVisualizzaNomeArticolo").checked === true
      )
        msg += "<td><center>" + arrayByTelaioID[i].Articolo + "</center></td>";
      if (
        document.getElementById("checkVisualizzaNomeArmatura1").checked === true
      )
        msg += "<td><center>" + arrayByTelaioID[i].Armatura1 + "</center></td>";
      if (
        document.getElementById("checkVisualizzaNomeArmatura2").checked === true
      )
        msg += "<td><center>" + arrayByTelaioID[i].Armatura2 + "</center></td>";
      if (
        document.getElementById("checkVisualizzaNomeDisegno").checked === true
      )
        msg += "<td><center>" + arrayByTelaioID[i].Disegno + "</center></td>";
      if (
        document.getElementById("checkVisualizzaLottoFilatoA").checked === true
      )
        msg +=
          "<td><center>" + arrayByTelaioID[i].LottoOrditoA + "</center></td>";
      if (
        document.getElementById("checkVisualizzaLottoFilatoB").checked === true
      )
        msg +=
          "<td><center>" + arrayByTelaioID[i].LottoOrditoB + "</center></td>";
      if (
        document.getElementById("checkVisualizzaLottoFilatoTrama").checked ===
        true
      )
        msg +=
          "<td><center>" + arrayByTelaioID[i].LottoTrama + "</center></td>";
      msg += "</tr>";
    }

    if ($.fn.DataTable.isDataTable("#dataTables-eventi")) {
      var dataTable = $("#dataTables-eventi").dataTable();
      dataTable.api().table().destroy();
    }
    document.getElementById("dataTables-eventi-thead").innerHTML = thead;
    document.getElementById("dataTables-eventi-body").innerHTML = msg;

    $.ajax({
      dataType: "json",
      url: "./JSONmsg.php",
      type: "POST",
      cache: false,
      data: "type=6",
      success: function (risposta) {
        let date = new Date();
        $("#dataTables-eventi").DataTable({
          retrieve: true,
          responsive: true,
          pageLength: 25,
          order: [[0, "desc"]],
          language: {
            emptyTable: risposta.sEmptyTable,
            info: risposta.sInfo,
            infoEmpty: risposta.sInfoEmpty,
            infoFiltered: risposta.sInfoFiltered,
            infoPostFix: risposta.sInfoPostFix,
            thousands: risposta.sInfoThousands,
            lengthMenu: risposta.sLengthMenu,
            loadingRecords: risposta.sLoadingRecords,
            processing: risposta.sProcessing,
            search: risposta.sSearch,
            zeroRecords: risposta.sZeroRecords,
            paginate: {
              first: risposta.oPaginate.sFirst,
              last: risposta.oPaginate.sLast,
              next: risposta.oPaginate.sNext,
              previous: risposta.oPaginate.sPrevious,
            },
            aria: {
              sortAscending: risposta.sSortAscending,
              sortDescending: risposta.sDescending,
            },
          },
          buttons: [
            {
              extend: "excelHtml5",
              filename: bLoomId
                ? `${pageName}_${
                    document.getElementById("storicoTitle").innerHTML
                  }_${new Date(
                    date.getTime() - date.getTimezoneOffset() * 60000
                  ).toISOString()}`
                : `${pageName}_${new Date(
                    date.getTime() - date.getTimezoneOffset() * 60000
                  ).toISOString()}`,
              messageTop: filters,
            },
            {
              extend: "csvHtml5",
              filename: bLoomId
                ? `${pageName}_${
                    document.getElementById("storicoTitle").innerHTML
                  }_${new Date(
                    date.getTime() - date.getTimezoneOffset() * 60000
                  ).toISOString()}`
                : `${pageName}_${new Date(
                    date.getTime() - date.getTimezoneOffset() * 60000
                  ).toISOString()}`,
              messageTop: filters,
            },
            {
              extend: "pdfHtml5",
              filename: bLoomId
                ? `${pageName}_${
                    document.getElementById("storicoTitle").innerHTML
                  }_${new Date(
                    date.getTime() - date.getTimezoneOffset() * 60000
                  ).toISOString()}`
                : `${pageName}_${new Date(
                    date.getTime() - date.getTimezoneOffset() * 60000
                  ).toISOString()}`,
              messageTop: filters,
            },
            {
              extend: "print",
              filename: bLoomId
                ? `${pageName}_${
                    document.getElementById("storicoTitle").innerHTML
                  }_${new Date(
                    date.getTime() - date.getTimezoneOffset() * 60000
                  ).toISOString()}`
                : `${pageName}_${new Date(
                    date.getTime() - date.getTimezoneOffset() * 60000
                  ).toISOString()}`,
              messageTop: filters,
            },
          ],
          dom:
            "<'row'<'col-lg-4'l><'col-lg-4'><'col-lg-4'f>>" +
            "<'row'<'col-lg-12'tr>>" +
            "<'row'<'col-lg-3'i><'col-lg-1 buttonTitle'><'col-lg-3'B><'col-lg-5'p>>",
        });

        $("div.buttonTitle").html(
          `<div class="dataTables_info pull-right">${buttonsTitle}:</b>`
        );
      },
    });
  }
}

//type=7
function getRTinfo(identifier) {
  var xmlhttp = new XMLHttpRequest();
  var url = "./JSONmsg.php";
  xmlhttp.onreadystatechange = function () {
    if (xmlhttp.readyState === 4 && xmlhttp.status === 200) {
      getMsgS(xmlhttp.responseText);
    }
  };
  xmlhttp.open("POST", url, true);
  xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
  xmlhttp.send("type=7&id=" + identifier);

  function getMsgS(response) {
    var arr = JSON.parse(response);
    document.getElementById("IDtelaio").innerHTML = arr.tab1.IDtelaio;
    document.getElementById("nome_telaio").innerHTML = arr.tab1.NomeTelaio;
    document.getElementById("warnings").innerHTML = arr.tab1.Warnings;
    document.getElementById("stato_produzione").innerHTML =
      arr.tab2.StatoProduzione +
      " " +
      getIconProductionState(arr.tab2.CodiceStatoProduzione);
    document.getElementById("modello_telaio").innerHTML = arr.tab1.Modello;
    if (arr.tab1.ModelloType !== "1" && arr.tab1.ModelloType !== 1) {
      //telaio a pinza e spugna
      document.querySelectorAll(".noPinza").forEach((el) => {
        el.style.display = "none";
      });
    }
    document.getElementById("link_telaio").value = arr.tab1.IP;
    document.getElementById("ultimo_agg").innerHTML = convertiData(
      arr.tab2.Update
    );
    document.getElementById("velocita_telaio").innerHTML = arr.tab2.Velocita;
    document.getElementById("resa").innerHTML =
      convertiResa(arr.tab2.Resa).toFixed(1) + " %";
    document.getElementById("tipo_produzione").innerHTML = arr.tab2.TipoP;

    if (arr.tab2.CodTipoP === "1" || arr.tab2.CodTipoP === "6") { //se la produzione è in metri o yarde
      document.getElementById("prod_effettuata").innerHTML = parseFloat(
        arr.tab2.ProdFatta.replace(",", ".")
      ).toFixed(2);
      document.getElementById("prod_desiderata").innerHTML = parseFloat(
        arr.tab2.ProdDesiderata.replace(",", ".")
      ).toFixed(2);
    } else {
      document.getElementById("prod_effettuata").innerHTML = " --- ";

      document.getElementById("prod_desiderata").innerHTML =  " --- ";
    }

    //document.getElementById("tempo_da_inizio").innerHTML = myPrettyTimelength(arr.tab2.TempoStart); //converti formato
    //Fix Chiara per dato sporco nuovo protocollo
    // if (arr.tab2.TempoEnd !== "-1") {
    if (arr.tab2.CodTipoP !== "0") {
      document.getElementById("tempo_mancante").innerHTML = myPrettyTimelength(
        arr.tab2.TempoEnd
      ); //converti formato
    } else {
      document.getElementById("tempo_mancante").innerHTML = "---";
    }
    let rfid = isIdentile(arr.tab2.RFID, "?") ? "---" : arr.tab2.RFID;
    document.getElementById("transponderRFID").innerHTML = rfid;
    document.getElementById("consumo").innerHTML = convertiConsumoAria(
      arr.tab2.Consumo
    );
    document.getElementById("densita_trama").innerHTML = convertiDensitaTrama(
      arr.tab3.DensitaTrama
    );
    document.getElementById("numero_subbi").innerHTML = arr.tab3.NSubbi;
    document.getElementById("tensione_ordito1").innerHTML =
      convertiTensioneOrdito(arr.tab3.TOrdito1);
    document.getElementById("tensione_ordito2").innerHTML =
      convertiTensioneOrdito(arr.tab3.TOrdito2);
    document.getElementById("tensione_ordito3").innerHTML =
      convertiTensioneOrdito(arr.tab3.TOrdito3);
    document.getElementById("tensione_ordito4").innerHTML =
      convertiTensioneOrdito(arr.tab3.TOrdito4);
    document.getElementById("t_mancante_sub1").innerHTML = myPrettyTimelength(
      arr.tab3.TSubbio1
    );
    document.getElementById("t_mancante_sub2").innerHTML = myPrettyTimelength(
      arr.tab3.TSubbio2
    );
    document.getElementById("t_mancante_sub3").innerHTML = myPrettyTimelength(
      arr.tab3.TSubbio3
    );
    // document.getElementById("t_mancante_sub4").innerHTML = myPrettyTimelength(
    //   arr.tab3.TSubbio4
    // );

    document.getElementById("turno").innerHTML = arr.tab4.Turno;
    document.getElementById("armatura1").innerHTML = arr.tab4.Armatura1;
    document.getElementById("armatura2").innerHTML = arr.tab4.Armatura2;
    document.getElementById("disegno").innerHTML = arr.tab4.Disegno;
    document.getElementById("lottoorditoA").innerHTML = arr.tab4.LottoOrditoA;
    document.getElementById("lottoorditoB").innerHTML = arr.tab4.LottoOrditoB;
    document.getElementById("lottotrama").innerHTML = arr.tab4.LottoTrama;
    //document.getElementById("dichiarazione").innerHTML = arr.tab4.Dichiarazione;
    document.getElementById("numeropezza").innerHTML = arr.tab4.NumeroPezza;
    document.getElementById("articolo").innerHTML = arr.tab4.Articolo;
    //Nuovi campi 2023
    document.getElementById("pezzeprogrammate").innerHTML =
      arr.tab3.PezzeProgrammate;
    document.getElementById("pezzeprodotte").innerHTML =
      arr.tab3.PezzeProdotte;
    document.getElementById("metriTotaliSubbio1").innerHTML =
      arr.tab3.MetriTotaliSubbio1 / 100;
    document.getElementById("metriTotaliSubbio2").innerHTML =
      arr.tab3.MetriTotaliSubbio2 / 100;
    document.getElementById("metriTotaliSubbio3").innerHTML =
      arr.tab3.MetriTotaliSubbio3 / 100;
    document.getElementById("metriFattiSubbio1").innerHTML =
      arr.tab3.MetriFattiSubbio1 / 100;
    document.getElementById("metriFattiSubbio2").innerHTML =
      arr.tab3.MetriFattiSubbio2 / 100;
    document.getElementById("metriFattiSubbio3").innerHTML =
      arr.tab3.MetriFattiSubbio3 / 100;
  }
}

function getIconProductionState(status) {
  status = parseInt(status);
  var string = "<span class='";
  switch (status) {
    case 0:
      string += "icon-run";
      break;
    case 1:
    case 15:
    case 16:
      string += "icon-ordito";
      break;
    case 2:
    case 3:
    case 4:
    case 5:
    case 10:
    case 11:
    case 12:
    case 13:
    case 14:
      string += "icon-trama";
      break;
    case 17:
      string += "icon-produzione";
      break;
    case 18:
      string += "icon-manuale";
      break;
    case 19:
      string += "icon-altro";
      break;
    case 20:
      string += "icon-rocca";
      break;
    default:
      string += " ";
      break;
  }
  string += "'></span>";
  return string;
}

// type=9
function getEventMonitor() {
  var xmlhttp = new XMLHttpRequest();
  var url = "./JSONmsg.php";
  xmlhttp.onreadystatechange = function () {
    if (xmlhttp.readyState === 4 && xmlhttp.status === 200) {
      getMsg(xmlhttp.responseText);
    }
  };
  xmlhttp.open("POST", url, true);
  xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
  xmlhttp.send("type=9");

  function getMsg(response) {
    var arr = JSON.parse(response);
    arr.sort(function (a, b) {
      return new Date(b.Timestamp) - new Date(a.Timestamp);
    });
    var tmp = "";
    for (var i = 0; i < arr.length; i++) {
      if (arr[i].isAlarm !== "0")
        tmp +=
          '<div class="alert alert-danger alert-dismissable"><button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>' +
          arr[i].Contenuto;
      else
        tmp +=
          '<div class="alert alert-warning alert-dismissable"><button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>' +
          arr[i].Contenuto;
      tmp +=
        '<a class="alert-link" href=javascript:doTelaioPost(' +
        arr[i].IdTelaio +
        ")>&nbsp" +
        arr[i].IdTelaio +
        " " +
        arr[i].NomeTelaio +
        "</a>&nbsp (&nbsp" +
        arr[i].Timestamp +
        "&nbsp)</div>";
    }
    document.getElementById("monitorEventsPanel").innerHTML = tmp;
  }
}

// type=10
function getEventsOnTopHeader() {
  var xmlhttp = new XMLHttpRequest();
  var url = "./JSONmsg.php";
  xmlhttp.onreadystatechange = function () {
    if (xmlhttp.readyState === 4 && xmlhttp.status === 200) {
      getMsg(xmlhttp.responseText);
    }
  };
  xmlhttp.open("POST", url, true);
  xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
  xmlhttp.send("type=10");

  function getMsg(response) {
    if (response != null && response != undefined && response != "") {
      var arr = JSON.parse(response);
      document.getElementById("numberofevents").innerHTML = arr.length;
      arr.sort(function (a, b) {
        return new Date(b.Timestamp) - new Date(a.Timestamp);
      });

      var allarmeSTR = languageDictionary["allarme"];
      var avvisoSTR = languageDictionary["avviso"];
      var allSTR = languageDictionary["vedi_tutti"];

      var tmp = "";
      for (var i = 0; i < arr.length && i < 8; i++) {
        if (i !== 0) tmp += '<li class="divider"></li>';
        tmp +=
          '<li><a href="events_temporary.php">' +
          arr[i].Contenuto +
          "&nbsp<strong>" +
          arr[i].IdTelaio +
          " " +
          arr[i].NomeTelaio +
          "</strong>";
        if (arr[i].isAlarm === "1")
          tmp +=
            '<br /><span class="label label-danger">' +
            allarmeSTR +
            "</span>" +
            "<i> &nbsp;" +
            arr[i].Timestamp +
            "</i>";
        else
          tmp +=
            '<br /><span class="label label-warning">' +
            avvisoSTR +
            "</span>" +
            "<i> &nbsp;" +
            arr[i].Timestamp +
            "</i>";
        tmp += "</a></li>";
      }
      tmp +=
        '<li class="divider"></li><li><a href="operasyon_merkezi.php"><strong><center>' +
        allSTR +
        '&nbsp <span class="fa fa-angle-right"></span></center></strong></a></li>';
      document.getElementById("eventsontopheader").innerHTML = tmp;
    }
  }
}

//type=12
function getTelaiList() {
  var xmlhttp = new XMLHttpRequest();
  var url = "./JSONmsg.php";
  xmlhttp.onreadystatechange = function () {
    if (xmlhttp.readyState === 4 && xmlhttp.status === 200) {
      getMsg(xmlhttp.responseText);
    }
  };
  xmlhttp.open("POST", url, true);
  xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
  xmlhttp.send("type=12");

  function getMsg(response) {
    var arr = JSON.parse(response);
    var tmp = "";
    for (var i = 0; i < arr.length; i++) {
      tmp +=
        '<option value="' +
        arr[i].IdTelaio +
        '" tipoModello="' +
        arr[i].TipoModello +
        '">' +
        arr[i].IdTelaio +
        " " +
        arr[i].Telaio +
        "</option>";
    }
    document.getElementById("telaiList").innerHTML = tmp;
  }
}

//fine JSONmsgs.js

function doTelaioPost(identifier) {
  var myForm = document.createElement("form");
  myForm.style.display = "none";
  myForm.action = "./telai.php"; // the href of the link
  myForm.method = "GET";
  var myInput = document.createElement("input");
  myInput.name = "id";
  myInput.value = identifier;
  myForm.appendChild(myInput);
  document.body.appendChild(myForm); //necessario per firefox ed ie
  myForm.submit();
}

function openVista(newIdVista, zoomI, mx, my) {
  var myForm = document.createElement("form");
  myForm.style.display = "none";
  myForm.action = "./index.php"; // the href of the link
  myForm.method = "GET";
  var myInput = document.createElement("input");
  myInput.name = "idVista";
  myInput.value = newIdVista;
  myForm.appendChild(myInput);
  var myInput2 = document.createElement("input");
  myInput2.name = "zoomI";
  myInput2.value = zoomI;
  myForm.appendChild(myInput2);
  var myInput3 = document.createElement("input");
  myInput3.name = "moveX";
  myInput3.value = mx;
  myForm.appendChild(myInput3);
  var myInput4 = document.createElement("input");
  myInput4.name = "moveY";
  myInput4.value = my;
  myForm.appendChild(myInput4);
  document.body.appendChild(myForm); //necessario per firefox ed ie
  myForm.submit();
}

function getStatistiche(messaggioDatetimeNotValid) {
  let append = "";
  let selectedOptions;
  let arr = [];

  selectedOptions = $("#telaiList option:selected");
  if (selectedOptions.length > 0) {
    selectedOptions.each(function () {
      arr.push(this.value.trim());
    });
    append += "&id=" + arr.join(",");
  }

  selectedOptions = $("#gruppiList option:selected");
  if (selectedOptions.length > 0) {
    arr = [];
    selectedOptions.each(function () {
      arr.push(this.value.trim());
    });
    append += "&idG=" + arr.join(",");
  }

  selectedOptions = $("#saleList option:selected");
  if (selectedOptions.length > 0) {
    arr = [];
    selectedOptions.each(function () {
      arr.push(this.value.trim());
    });
    append += "&idS=" + arr.join(",");
  }

  selectedOptions = $("#turniList option:selected");
  if (selectedOptions.length > 0) {
    arr = [];
    selectedOptions.each(function () {
      arr.push("'" + this.value.trim() + "'");
    });
    append += "&turno=" + arr.join(",");
  }

  selectedOptions = $("#nomiArticoloList option:selected");
  if (selectedOptions.length > 0) {
    arr = [];
    selectedOptions.each(function () {
      arr.push("'" + this.value.trim() + "'");
    });
    append += "&art=" + arr.join(",");
  }

  selectedOptions = $("#nomiDisegnoList option:selected");
  if (selectedOptions.length > 0) {
    arr = [];
    selectedOptions.each(function () {
      arr.push("'" + this.value.trim() + "'");
    });
    append += "&dis=" + arr.join(",");
  }

  selectedOptions = $("#nomiArmaturaList option:selected");
  if (selectedOptions.length > 0) {
    arr = [];
    selectedOptions.each(function () {
      arr.push("'" + this.value.trim() + "'");
    });
    append += "&arm=" + arr.join(",");
  }

  selectedOptions = $("#lottoOrditoListA option:selected");
  if (selectedOptions.length > 0) {
    arr = [];
    selectedOptions.each(function () {
      arr.push("'" + this.value.trim() + "'");
    });
    append += "&ordA=" + arr.join(",");
  }

  selectedOptions = $("#lottoOrditoListB option:selected");
  if (selectedOptions.length > 0) {
    arr = [];
    selectedOptions.each(function () {
      arr.push("'" + this.value.trim() + "'");
    });
    append += "&ordB=" + arr.join(",");
  }

  selectedOptions = $("#lottoTramaList option:selected");
  if (selectedOptions.length > 0) {
    append +=
      "&tra=" +
      selectedOptions
        .map(function () {
          return "'" + this.value.trim() + "'";
        })
        .get()
        .join(",");
  }

  if (document.getElementById("optionsRadios1").checked === true) {
    //-1 giorno
    append += "&from='" + myPrettyDate(1) + "'";
    append += "&to='" + myPrettyDate(0) + "'";
  }
  if (document.getElementById("optionsRadios2").checked === true) {
    //-1 settimana
    append += "&from='" + myPrettyDate(2) + "'";
    append += "&to='" + myPrettyDate(0) + "'";
  }
  if (document.getElementById("optionsRadios3").checked === true) {
    //-1 mese
    append += "&from='" + myPrettyDate(3) + "'";
    append += "&to='" + myPrettyDate(0) + "'";
  }

  if (document.getElementById("optionsRadios4").checked === true) {
    //by user
    if (
      $("#datetimepicker6_input").val() !== "" &&
      $("#datetimepicker7_input").val() !== ""
    ) {
      append +=
        "&from='" +
        $("#datetimepicker6_input").val() +
        "'&to='" +
        $("#datetimepicker7_input").val() +
        "'";
    } else {
      BootstrapDialog.alert(messaggioDatetimeNotValid);
      return;
    }
  }

  //Aggiungere criteri
  if (document.getElementById("optionsPick1").checked === true) {
    //All
    append += "&crit=1";
  }
  if (document.getElementById("optionsPick2").checked === true) {
    //Any
    append += "&crit=2";
  }
  if (document.getElementById("optionsPick3").checked === true) {
    //Same
    append += "&crit=3";
  }

  //Aggiungere Picks
  var nPicks = document.getElementById("deltaPicks").value;
  if (nPicks > 0) {
    append += "&npicks=" + nPicks;
  }

  var xmlhttp = new XMLHttpRequest();
  var url = "./JSONmsg.php";
  xmlhttp.onreadystatechange = function () {
    if (xmlhttp.readyState === 4 && xmlhttp.status === 200) {
      getMsg(xmlhttp.responseText);
      waitingDialog.hide();
    }
  };
  waitingDialog.show();
  xmlhttp.open("POST", url, true);
  xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
  xmlhttp.send("type=20" + append);

  function getMsg(response) {
    var arr = JSON.parse(response);
    var considerAria = false;
    var considerPinza = false;
    if (Math.floor(arr.CodiceTipoTelai / 10) === 1) {
      considerAria = true;
      document.getElementById("considerAria").value = 1;
    } else {
      document.getElementById("considerAria").value = 0;
    }
    if (arr.CodiceTipoTelai % 10 === 1) {
      considerPinza = true;
      document.getElementById("considerPinza").value = 1;
    } else {
      document.getElementById("considerPinza").value = 0;
    }

    document.getElementById("battuteTotali").innerHTML = arr.BattuteTotali;
    if (arr.SisMisura === "0") {
      document.getElementById("lunghezzaTessuto").innerHTML =
        (arr.LunghezzaTessuta * 0.00109361).toFixed(2) + " yard";
    } else {
      document.getElementById("lunghezzaTessuto").innerHTML =
        (arr.LunghezzaTessuta / 1000).toFixed(2) + " m";
    }
    document.getElementById("tempoTotale").innerHTML = myPrettyTimelength(
      arr.TempoTotale
    );
    document.getElementById("tempoTotaleFermo").innerHTML = myPrettyTimelength(
      arr.TempoFermo
    );
    //document.getElementById('dataInizio').innerHTML = arr.TempoInizio;
    //document.getElementById('dataFine').innerHTML = arr.TempoFine;
    document.getElementById("barraResa").style.width =
      (arr.Resa / 100).toFixed(1) + "%";
    document.getElementById("PercResa").innerHTML =
      (arr.Resa / 100).toFixed(1) + "%";
    document.getElementById("NumeroTotaleFermateOrdito").innerHTML =
      arr.NumeroTotaleFermateOrdito;
    if (parseInt(arr.BattuteTotali) !== 0) {
      document.getElementById("NumeroPerBattuteFermateOrdito").innerHTML = (
        (100000 * arr.NumeroTotaleFermateOrdito) /
        arr.BattuteTotali
      ).toFixed(2);
      document.getElementById("NumeroPerBattuteFermateOrditoOra").innerHTML = (
        (3600 * arr.NumeroTotaleFermateOrdito) /
        (arr.TempoTotale - arr.TempoFermo)
      ).toFixed(2);
    } else {
      document.getElementById("NumeroPerBattuteFermateOrdito").innerHTML = 0;
      document.getElementById("NumeroPerBattuteFermateOrditoOra").innerHTML = 0;
    }
    document.getElementById("TempoTotaleFermoOrdito").innerHTML =
      myPrettyTimelength(arr.TempoTotaleFermoOrdito);
    document.getElementById("NumeroTotaleFermateTrama").innerHTML =
      arr.NumeroTotaleFermateTrama;
    if (parseInt(arr.BattuteTotali) !== 0) {
      document.getElementById("NumeroPerBattuteFermateTrama").innerHTML = (
        (100000 * arr.NumeroTotaleFermateTrama) /
        arr.BattuteTotali
      ).toFixed(2);
      document.getElementById("NumeroPerBattuteFermateTramaOra").innerHTML = (
        (3600 * arr.NumeroTotaleFermateTrama) /
        (arr.TempoTotale - arr.TempoFermo)
      ).toFixed(2);
    } else {
      document.getElementById("NumeroPerBattuteFermateTrama").innerHTML = 0;
      document.getElementById("NumeroPerBattuteFermateTramaOra").innerHTML = 0;
    }
    document.getElementById("TempoTotaleFermoTrama").innerHTML =
      myPrettyTimelength(arr.TempoTotaleFermoTrama);
    document.getElementById("NumeroTotaleFermateVarie").innerHTML =
      arr.NumeroTotaleFermateVarie;
    if (parseInt(arr.BattuteTotali) !== 0) {
      document.getElementById("NumeroPerBattuteFermateVarie").innerHTML = (
        (100000 * arr.NumeroTotaleFermateVarie) /
        arr.BattuteTotali
      ).toFixed(2);
      document.getElementById("NumeroPerBattuteFermateVarieOra").innerHTML = (
        (3600 * arr.NumeroTotaleFermateVarie) /
        (arr.TempoTotale - arr.TempoFermo)
      ).toFixed(2);
    } else {
      document.getElementById("NumeroPerBattuteFermateVarie").innerHTML = 0;
      document.getElementById("NumeroPerBattuteFermateVarieOra").innerHTML = 0;
    }
    document.getElementById("TempoTotaleFermoVarie").innerHTML =
      myPrettyTimelength(arr.TempoTotaleFermoVarie);

    if (considerAria) {
      document.getElementById("NumeroTotaleFermateFar").innerHTML =
        arr.NumeroTotaleFermateFar;
      document.getElementById("NumeroTotaleRipartenzeFar").innerHTML =
        arr.NumeroTotaleRipartenzeFar;
      if (parseInt(arr.NumeroTotaleFermateFar) !== 0) {
        document.getElementById("RapportoSuccessiFar").innerHTML =
          (
            (arr.NumeroTotaleRipartenzeFar / arr.NumeroTotaleFermateFar) *
            100
          ).toFixed(2) + "%";
      } else document.getElementById("RapportoSuccessiFar").innerHTML = 0;
    }

    var elements = document.getElementsByClassName("pinza");
    for (var i = 0; i < elements.length; i++) {
      elements[i].style.display = "none";
    }
    var elements = document.getElementsByClassName("aria");
    for (var i = 0; i < elements.length; i++) {
      elements[i].style.display = "none";
    }

    if (considerAria === false && considerPinza === false) {
      considerAria = true;
      considerPinza = true;
    }

    if (considerAria) {
      var elements = document.getElementsByClassName("aria");
      for (var i = 0; i < elements.length; i++) {
        elements[i].style.display = "";
      }
    }

    if (considerPinza) {
      var elements = document.getElementsByClassName("pinza");
      for (var i = 0; i < elements.length; i++) {
        elements[i].style.display = "";
      }
    }

    var BattuteTotali = arr.BattuteTotali;
    document.getElementById("NumeroFermateOrdito1").innerHTML =
      arr.NumeroFermateOrdito1;
    if (parseInt(arr.BattuteTotali) !== 0) {
      document.getElementById("NumeroPerBattuteFermateOrdito1").innerHTML = (
        (100000 * arr.NumeroFermateOrdito1) /
        BattuteTotali
      ).toFixed(2);
    } else
      document.getElementById("NumeroPerBattuteFermateOrdito1").innerHTML = 0;
    document.getElementById("TempoFermoOrdito1").innerHTML = myPrettyTimelength(
      arr.TempoFermoOrdito1
    );
    document.getElementById("NumeroFermateOrdito2").innerHTML =
      arr.NumeroFermateOrdito2;
    if (parseInt(arr.BattuteTotali) !== 0) {
      document.getElementById("NumeroPerBattuteFermateOrdito2").innerHTML = (
        (100000 * arr.NumeroFermateOrdito2) /
        BattuteTotali
      ).toFixed(2);
    } else
      document.getElementById("NumeroPerBattuteFermateOrdito2").innerHTML = 0;
    document.getElementById("TempoFermoOrdito2").innerHTML = myPrettyTimelength(
      arr.TempoFermoOrdito2
    );
    document.getElementById("NumeroFermateOrdito3").innerHTML =
      arr.NumeroFermateOrdito3;
    if (parseInt(arr.BattuteTotali) !== 0) {
      document.getElementById("NumeroPerBattuteFermateOrdito3").innerHTML = (
        (100000 * arr.NumeroFermateOrdito3) /
        BattuteTotali
      ).toFixed(2);
    } else
      document.getElementById("NumeroPerBattuteFermateOrdito3").innerHTML = 0;
    document.getElementById("TempoFermoOrdito3").innerHTML = myPrettyTimelength(
      arr.TempoFermoOrdito3
    );
    document.getElementById("NumeroFermateOrdito4").innerHTML =
      arr.NumeroFermateOrdito4;
    if (parseInt(arr.BattuteTotali) !== 0) {
      document.getElementById("NumeroPerBattuteFermateOrdito4").innerHTML = (
        (100000 * arr.NumeroFermateOrdito4) /
        BattuteTotali
      ).toFixed(2);
    } else
      document.getElementById("NumeroPerBattuteFermateOrdito4").innerHTML = 0;
    document.getElementById("TempoFermoOrdito4").innerHTML = myPrettyTimelength(
      arr.TempoFermoOrdito4
    );
    document.getElementById("NumeroFermateCimossa1").innerHTML =
      arr.NumeroFermateCimossa1;
    if (parseInt(arr.BattuteTotali) !== 0) {
      document.getElementById("NumeroPerBattuteFermateCimossa1").innerHTML = (
        (100000 * arr.NumeroFermateCimossa1) /
        BattuteTotali
      ).toFixed(2);
    } else
      document.getElementById("NumeroPerBattuteFermateCimossa1").innerHTML = 0;
    document.getElementById("TempoFermoCimossa1").innerHTML =
      myPrettyTimelength(arr.TempoFermoCimossa1);
    document.getElementById("NumeroFermateCimossa2").innerHTML =
      arr.NumeroFermateCimossa2;
    if (parseInt(arr.BattuteTotali) !== 0) {
      document.getElementById("NumeroPerBattuteFermateCimossa2").innerHTML = (
        (100000 * arr.NumeroFermateCimossa2) /
        BattuteTotali
      ).toFixed(2);
    } else
      document.getElementById("NumeroPerBattuteFermateCimossa2").innerHTML = 0;
    document.getElementById("TempoFermoCimossa2").innerHTML =
      myPrettyTimelength(arr.TempoFermoCimossa2);
    document.getElementById("NumeroFermateCimossa3").innerHTML =
      arr.NumeroFermateCimossa3;
    if (parseInt(arr.BattuteTotali) !== 0) {
      document.getElementById("NumeroPerBattuteFermateCimossa3").innerHTML = (
        (100000 * arr.NumeroFermateCimossa3) /
        BattuteTotali
      ).toFixed(2);
    } else
      document.getElementById("NumeroPerBattuteFermateCimossa3").innerHTML = 0;
    document.getElementById("TempoFermoCimossa3").innerHTML =
      myPrettyTimelength(arr.TempoFermoCimossa3);
    document.getElementById("NumeroFermateCimossa4").innerHTML =
      arr.NumeroFermateCimossa4;
    if (parseInt(arr.BattuteTotali) !== 0) {
      document.getElementById("NumeroPerBattuteFermateCimossa4").innerHTML = (
        (100000 * arr.NumeroFermateCimossa4) /
        BattuteTotali
      ).toFixed(2);
    } else
      document.getElementById("NumeroPerBattuteFermateCimossa4").innerHTML = 0;
    document.getElementById("TempoFermoCimossa4").innerHTML =
      myPrettyTimelength(arr.TempoFermoCimossa4);
    document.getElementById("NumeroFermateSplitz1").innerHTML =
      arr.NumeroFermateSplitz1;
    if (parseInt(arr.BattuteTotali) !== 0) {
      document.getElementById("NumeroPerBattuteFermateSplitz1").innerHTML = (
        (100000 * arr.NumeroFermateSplitz1) /
        BattuteTotali
      ).toFixed(2);
    } else
      document.getElementById("NumeroPerBattuteFermateSplitz1").innerHTML = 0;
    document.getElementById("TempoFermoSplitz1").innerHTML = myPrettyTimelength(
      arr.TempoFermoSplitz1
    );
    document.getElementById("NumeroFermateSplitz2").innerHTML =
      arr.NumeroFermateSplitz2;
    if (parseInt(arr.BattuteTotali) !== 0) {
      document.getElementById("NumeroPerBattuteFermateSplitz2").innerHTML = (
        (100000 * arr.NumeroFermateSplitz2) /
        BattuteTotali
      ).toFixed(2);
    } else
      document.getElementById("NumeroPerBattuteFermateSplitz2").innerHTML = 0;
    document.getElementById("TempoFermoSplitz2").innerHTML = myPrettyTimelength(
      arr.TempoFermoSplitz2
    );
    document.getElementById("NumeroFermateSplitz3").innerHTML =
      arr.NumeroFermateSplitz3;
    if (parseInt(arr.BattuteTotali) !== 0) {
      document.getElementById("NumeroPerBattuteFermateSplitz3").innerHTML = (
        (100000 * arr.NumeroFermateSplitz3) /
        BattuteTotali
      ).toFixed(2);
    } else
      document.getElementById("NumeroPerBattuteFermateSplitz3").innerHTML = 0;
    document.getElementById("TempoFermoSplitz3").innerHTML = myPrettyTimelength(
      arr.TempoFermoSplitz3
    );
    document.getElementById("NumeroFermateSplitz4").innerHTML =
      arr.NumeroFermateSplitz4;
    if (parseInt(arr.BattuteTotali) !== 0) {
      document.getElementById("NumeroPerBattuteFermateSplitz4").innerHTML = (
        (100000 * arr.NumeroFermateSplitz4) /
        BattuteTotali
      ).toFixed(2);
    } else
      document.getElementById("NumeroPerBattuteFermateSplitz4").innerHTML = 0;
    document.getElementById("TempoFermoSplitz4").innerHTML = myPrettyTimelength(
      arr.TempoFermoSplitz4
    );
    if (considerPinza) {
      document.getElementById("NumeroTotaleFermateTramaA").innerHTML =
        arr.NumeroTotaleFermateTramaA;
      if (parseInt(arr.BattuteTotali) !== 0) {
        document.getElementById("NumeroPerBattuteFermateTramaA").innerHTML = (
          (100000 * arr.NumeroTotaleFermateTramaA) /
          BattuteTotali
        ).toFixed(2);
      } else
        document.getElementById("NumeroPerBattuteFermateTramaA").innerHTML = 0;
      document.getElementById("TempoTotaleFermoTramaA").innerHTML =
        myPrettyTimelength(arr.TempoTotaleFermoTramaA);
      document.getElementById("NumeroTotaleFermateTramaP").innerHTML =
        arr.NumeroTotaleFermateTramaP;
      if (parseInt(arr.BattuteTotali) !== 0) {
        document.getElementById("NumeroPerBattuteFermateTramaP").innerHTML = (
          (100000 * arr.NumeroTotaleFermateTramaP) /
          BattuteTotali
        ).toFixed(2);
      } else
        document.getElementById("NumeroPerBattuteFermateTramaP").innerHTML = 0;
      document.getElementById("TempoTotaleFermoTramaP").innerHTML =
        myPrettyTimelength(arr.TempoTotaleFermoTramaP);
      document.getElementById("NumeroTotaleFermateTramaLH").innerHTML =
        arr.NumeroTotaleFermateTramaLH;
      if (parseInt(arr.BattuteTotali) !== 0) {
        document.getElementById("NumeroPerBattuteFermateTramaLH").innerHTML = (
          (100000 * arr.NumeroTotaleFermateTramaLH) /
          BattuteTotali
        ).toFixed(2);
      } else
        document.getElementById("NumeroPerBattuteFermateTramaLH").innerHTML = 0;
      document.getElementById("TempoTotaleFermoTramaLH").innerHTML =
        myPrettyTimelength(arr.TempoTotaleFermoTramaLH);
      document.getElementById("NumeroTotaleFermateTramaE").innerHTML =
        arr.NumeroTotaleFermateTramaE;
      if (parseInt(arr.BattuteTotali) !== 0) {
        document.getElementById("NumeroPerBattuteFermateTramaE").innerHTML = (
          (100000 * arr.NumeroTotaleFermateTramaE) /
          BattuteTotali
        ).toFixed(2);
      } else
        document.getElementById("NumeroPerBattuteFermateTramaE").innerHTML = 0;
      document.getElementById("TempoTotaleFermoTramaE").innerHTML =
        myPrettyTimelength(arr.TempoTotaleFermoTramaE);
      document.getElementById("NumeroTotaleFermateTramaRH").innerHTML =
        arr.NumeroTotaleFermateTramaRH;
      if (parseInt(arr.BattuteTotali) !== 0) {
        document.getElementById("NumeroPerBattuteFermateTramaRH").innerHTML = (
          (100000 * arr.NumeroTotaleFermateTramaRH) /
          BattuteTotali
        ).toFixed(2);
      } else
        document.getElementById("NumeroPerBattuteFermateTramaRH").innerHTML = 0;
      document.getElementById("TempoTotaleFermoTramaRH").innerHTML =
        myPrettyTimelength(arr.TempoTotaleFermoTramaRH);
      document.getElementById("NumeroFermateTramaA1").innerHTML =
        arr.NumeroFermateTramaA1;
      document.getElementById("NumeroFermateTramaA2").innerHTML =
        arr.NumeroFermateTramaA2;
      document.getElementById("NumeroFermateTramaA3").innerHTML =
        arr.NumeroFermateTramaA3;
      document.getElementById("NumeroFermateTramaA4").innerHTML =
        arr.NumeroFermateTramaA4;
      document.getElementById("NumeroFermateTramaA5").innerHTML =
        arr.NumeroFermateTramaA5;
      document.getElementById("NumeroFermateTramaA6").innerHTML =
        arr.NumeroFermateTramaA6;
      document.getElementById("NumeroFermateTramaA7").innerHTML =
        arr.NumeroFermateTramaA7;
      document.getElementById("NumeroFermateTramaA8").innerHTML =
        arr.NumeroFermateTramaA8;
      document.getElementById("NumeroFermateTramaA9").innerHTML =
        arr.NumeroFermateTramaA9;
      document.getElementById("NumeroFermateTramaA10").innerHTML =
        arr.NumeroFermateTramaA10;
      document.getElementById("NumeroFermateTramaA11").innerHTML =
        arr.NumeroFermateTramaA11;
      document.getElementById("NumeroFermateTramaA12").innerHTML =
        arr.NumeroFermateTramaA12;
      document.getElementById("NumeroFermateTramaA13").innerHTML =
        arr.NumeroFermateTramaA13;
      document.getElementById("NumeroFermateTramaA14").innerHTML =
        arr.NumeroFermateTramaA14;
      document.getElementById("NumeroFermateTramaA15").innerHTML =
        arr.NumeroFermateTramaA15;
      document.getElementById("NumeroFermateTramaA16").innerHTML =
        arr.NumeroFermateTramaA16;
      document.getElementById("NumeroFermateTramaP1").innerHTML =
        arr.NumeroFermateTramaP1;
      document.getElementById("NumeroFermateTramaP2").innerHTML =
        arr.NumeroFermateTramaP2;
      document.getElementById("NumeroFermateTramaP3").innerHTML =
        arr.NumeroFermateTramaP3;
      document.getElementById("NumeroFermateTramaP4").innerHTML =
        arr.NumeroFermateTramaP4;
      document.getElementById("NumeroFermateTramaP5").innerHTML =
        arr.NumeroFermateTramaP5;
      document.getElementById("NumeroFermateTramaP6").innerHTML =
        arr.NumeroFermateTramaP6;
      document.getElementById("NumeroFermateTramaP7").innerHTML =
        arr.NumeroFermateTramaP7;
      document.getElementById("NumeroFermateTramaP8").innerHTML =
        arr.NumeroFermateTramaP8;
      document.getElementById("NumeroFermateTramaP9").innerHTML =
        arr.NumeroFermateTramaP9;
      document.getElementById("NumeroFermateTramaP10").innerHTML =
        arr.NumeroFermateTramaP10;
      document.getElementById("NumeroFermateTramaP11").innerHTML =
        arr.NumeroFermateTramaP11;
      document.getElementById("NumeroFermateTramaP12").innerHTML =
        arr.NumeroFermateTramaP12;
      document.getElementById("NumeroFermateTramaP13").innerHTML =
        arr.NumeroFermateTramaP13;
      document.getElementById("NumeroFermateTramaP14").innerHTML =
        arr.NumeroFermateTramaP14;
      document.getElementById("NumeroFermateTramaP15").innerHTML =
        arr.NumeroFermateTramaP15;
      document.getElementById("NumeroFermateTramaP16").innerHTML =
        arr.NumeroFermateTramaP16;
      document.getElementById("NumeroFermateTramaLH1").innerHTML =
        arr.NumeroFermateTramaLH1;
      document.getElementById("NumeroFermateTramaLH2").innerHTML =
        arr.NumeroFermateTramaLH2;
      document.getElementById("NumeroFermateTramaLH3").innerHTML =
        arr.NumeroFermateTramaLH3;
      document.getElementById("NumeroFermateTramaLH4").innerHTML =
        arr.NumeroFermateTramaLH4;
      document.getElementById("NumeroFermateTramaLH5").innerHTML =
        arr.NumeroFermateTramaLH5;
      document.getElementById("NumeroFermateTramaLH6").innerHTML =
        arr.NumeroFermateTramaLH6;
      document.getElementById("NumeroFermateTramaLH7").innerHTML =
        arr.NumeroFermateTramaLH7;
      document.getElementById("NumeroFermateTramaLH8").innerHTML =
        arr.NumeroFermateTramaLH8;
      document.getElementById("NumeroFermateTramaLH9").innerHTML =
        arr.NumeroFermateTramaLH9;
      document.getElementById("NumeroFermateTramaLH10").innerHTML =
        arr.NumeroFermateTramaLH10;
      document.getElementById("NumeroFermateTramaLH11").innerHTML =
        arr.NumeroFermateTramaLH11;
      document.getElementById("NumeroFermateTramaLH12").innerHTML =
        arr.NumeroFermateTramaLH12;
      document.getElementById("NumeroFermateTramaLH13").innerHTML =
        arr.NumeroFermateTramaLH13;
      document.getElementById("NumeroFermateTramaLH14").innerHTML =
        arr.NumeroFermateTramaLH14;
      document.getElementById("NumeroFermateTramaLH15").innerHTML =
        arr.NumeroFermateTramaLH15;
      document.getElementById("NumeroFermateTramaLH16").innerHTML =
        arr.NumeroFermateTramaLH16;
      document.getElementById("NumeroFermateTramaE1").innerHTML =
        arr.NumeroFermateTramaE1;
      document.getElementById("NumeroFermateTramaE2").innerHTML =
        arr.NumeroFermateTramaE2;
      document.getElementById("NumeroFermateTramaE3").innerHTML =
        arr.NumeroFermateTramaE3;
      document.getElementById("NumeroFermateTramaE4").innerHTML =
        arr.NumeroFermateTramaE4;
      document.getElementById("NumeroFermateTramaE5").innerHTML =
        arr.NumeroFermateTramaE5;
      document.getElementById("NumeroFermateTramaE6").innerHTML =
        arr.NumeroFermateTramaE6;
      document.getElementById("NumeroFermateTramaE7").innerHTML =
        arr.NumeroFermateTramaE7;
      document.getElementById("NumeroFermateTramaE8").innerHTML =
        arr.NumeroFermateTramaE8;
      document.getElementById("NumeroFermateTramaE9").innerHTML =
        arr.NumeroFermateTramaE9;
      document.getElementById("NumeroFermateTramaE10").innerHTML =
        arr.NumeroFermateTramaE10;
      document.getElementById("NumeroFermateTramaE11").innerHTML =
        arr.NumeroFermateTramaE11;
      document.getElementById("NumeroFermateTramaE12").innerHTML =
        arr.NumeroFermateTramaE12;
      document.getElementById("NumeroFermateTramaE13").innerHTML =
        arr.NumeroFermateTramaE13;
      document.getElementById("NumeroFermateTramaE14").innerHTML =
        arr.NumeroFermateTramaE14;
      document.getElementById("NumeroFermateTramaE15").innerHTML =
        arr.NumeroFermateTramaE15;
      document.getElementById("NumeroFermateTramaE16").innerHTML =
        arr.NumeroFermateTramaE16;
      document.getElementById("NumeroFermateTramaRH1").innerHTML =
        arr.NumeroFermateTramaRH1;
      document.getElementById("NumeroFermateTramaRH2").innerHTML =
        arr.NumeroFermateTramaRH2;
      document.getElementById("NumeroFermateTramaRH3").innerHTML =
        arr.NumeroFermateTramaRH3;
      document.getElementById("NumeroFermateTramaRH4").innerHTML =
        arr.NumeroFermateTramaRH4;
      document.getElementById("NumeroFermateTramaRH5").innerHTML =
        arr.NumeroFermateTramaRH5;
      document.getElementById("NumeroFermateTramaRH6").innerHTML =
        arr.NumeroFermateTramaRH6;
      document.getElementById("NumeroFermateTramaRH7").innerHTML =
        arr.NumeroFermateTramaRH7;
      document.getElementById("NumeroFermateTramaRH8").innerHTML =
        arr.NumeroFermateTramaRH8;
      document.getElementById("NumeroFermateTramaRH9").innerHTML =
        arr.NumeroFermateTramaRH9;
      document.getElementById("NumeroFermateTramaRH10").innerHTML =
        arr.NumeroFermateTramaRH10;
      document.getElementById("NumeroFermateTramaRH11").innerHTML =
        arr.NumeroFermateTramaRH11;
      document.getElementById("NumeroFermateTramaRH12").innerHTML =
        arr.NumeroFermateTramaRH12;
      document.getElementById("NumeroFermateTramaRH13").innerHTML =
        arr.NumeroFermateTramaRH13;
      document.getElementById("NumeroFermateTramaRH14").innerHTML =
        arr.NumeroFermateTramaRH14;
      document.getElementById("NumeroFermateTramaRH15").innerHTML =
        arr.NumeroFermateTramaRH15;
      document.getElementById("NumeroFermateTramaRH16").innerHTML =
        arr.NumeroFermateTramaRH16;
    }

    if (considerAria) {
      document.getElementById("NumeroTotaleFermateTramaTU").innerHTML =
        arr.NumeroTotaleFermateTramaTU;
      if (parseInt(arr.BattuteTotali) !== 0) {
        document.getElementById("NumeroPerBattuteFermateTramaTU").innerHTML = (
          (100000 * arr.NumeroTotaleFermateTramaTU) /
          BattuteTotali
        ).toFixed(2);
      } else
        document.getElementById("NumeroPerBattuteFermateTramaTU").innerHTML = 0;
      document.getElementById("TempoTotaleFermoTramaTU").innerHTML =
        myPrettyTimelength(arr.TempoTotaleFermoTramaTU);
      document.getElementById("NumeroTotaleFermateTramaAD").innerHTML =
        arr.NumeroTotaleFermateTramaAD;
      if (parseInt(arr.BattuteTotali) !== 0) {
        document.getElementById("NumeroPerBattuteFermateTramaAD").innerHTML = (
          (100000 * arr.NumeroTotaleFermateTramaAD) /
          BattuteTotali
        ).toFixed(2);
      } else
        document.getElementById("NumeroPerBattuteFermateTramaAD").innerHTML = 0;
      document.getElementById("TempoTotaleFermoTramaAD").innerHTML =
        myPrettyTimelength(arr.TempoTotaleFermoTramaAD);
      document.getElementById("NumeroTotaleFermateTramaL").innerHTML =
        arr.NumeroTotaleFermateTramaL;
      if (parseInt(arr.BattuteTotali) !== 0) {
        document.getElementById("NumeroPerBattuteFermateTramaL").innerHTML = (
          (100000 * arr.NumeroTotaleFermateTramaL) /
          BattuteTotali
        ).toFixed(2);
      } else
        document.getElementById("NumeroPerBattuteFermateTramaL").innerHTML = 0;
      document.getElementById("TempoTotaleFermoTramaL").innerHTML =
        myPrettyTimelength(arr.TempoTotaleFermoTramaL);
      document.getElementById("NumeroTotaleFermateTramaS").innerHTML =
        arr.NumeroTotaleFermateTramaS;
      if (parseInt(arr.BattuteTotali) !== 0) {
        document.getElementById("NumeroPerBattuteFermateTramaS").innerHTML = (
          (100000 * arr.NumeroTotaleFermateTramaS) /
          BattuteTotali
        ).toFixed(2);
      } else
        document.getElementById("NumeroPerBattuteFermateTramaS").innerHTML = 0;
      document.getElementById("TempoTotaleFermoTramaS").innerHTML =
        myPrettyTimelength(arr.TempoTotaleFermoTramaS);
      document.getElementById("NumeroFermateTramaL1").innerHTML =
        arr.NumeroFermateTramaL1;
      document.getElementById("NumeroFermateTramaL2").innerHTML =
        arr.NumeroFermateTramaL2;
      document.getElementById("NumeroFermateTramaL3").innerHTML =
        arr.NumeroFermateTramaL3;
      document.getElementById("NumeroFermateTramaL4").innerHTML =
        arr.NumeroFermateTramaL4;
      document.getElementById("NumeroFermateTramaL5").innerHTML =
        arr.NumeroFermateTramaL5;
      document.getElementById("NumeroFermateTramaL6").innerHTML =
        arr.NumeroFermateTramaL6;
      document.getElementById("NumeroFermateTramaL7").innerHTML =
        arr.NumeroFermateTramaL7;
      document.getElementById("NumeroFermateTramaL8").innerHTML =
        arr.NumeroFermateTramaL8;
      document.getElementById("NumeroFermateTramaS1").innerHTML =
        arr.NumeroFermateTramaS1;
      document.getElementById("NumeroFermateTramaS2").innerHTML =
        arr.NumeroFermateTramaS2;
      document.getElementById("NumeroFermateTramaS3").innerHTML =
        arr.NumeroFermateTramaS3;
      document.getElementById("NumeroFermateTramaS4").innerHTML =
        arr.NumeroFermateTramaS4;
      document.getElementById("NumeroFermateTramaS5").innerHTML =
        arr.NumeroFermateTramaS5;
      document.getElementById("NumeroFermateTramaS6").innerHTML =
        arr.NumeroFermateTramaS6;
      document.getElementById("NumeroFermateTramaS7").innerHTML =
        arr.NumeroFermateTramaS7;
      document.getElementById("NumeroFermateTramaS8").innerHTML =
        arr.NumeroFermateTramaS8;
      document.getElementById("NumeroFermateTramaTU1").innerHTML =
        arr.NumeroFermateTramaTU1;
      document.getElementById("NumeroFermateTramaTU2").innerHTML =
        arr.NumeroFermateTramaTU2;
      document.getElementById("NumeroFermateTramaTU3").innerHTML =
        arr.NumeroFermateTramaTU3;
      document.getElementById("NumeroFermateTramaTU4").innerHTML =
        arr.NumeroFermateTramaTU4;
      document.getElementById("NumeroFermateTramaTU5").innerHTML =
        arr.NumeroFermateTramaTU5;
      document.getElementById("NumeroFermateTramaTU6").innerHTML =
        arr.NumeroFermateTramaTU6;
      document.getElementById("NumeroFermateTramaTU7").innerHTML =
        arr.NumeroFermateTramaTU7;
      document.getElementById("NumeroFermateTramaTU8").innerHTML =
        arr.NumeroFermateTramaTU8;
      document.getElementById("NumeroFermateTramaAD1").innerHTML =
        arr.NumeroFermateTramaAD1;
      document.getElementById("NumeroFermateTramaAD2").innerHTML =
        arr.NumeroFermateTramaAD2;
      document.getElementById("NumeroFermateTramaAD3").innerHTML =
        arr.NumeroFermateTramaAD3;
      document.getElementById("NumeroFermateTramaAD4").innerHTML =
        arr.NumeroFermateTramaAD4;
      document.getElementById("NumeroFermateTramaAD5").innerHTML =
        arr.NumeroFermateTramaAD5;
      document.getElementById("NumeroFermateTramaAD6").innerHTML =
        arr.NumeroFermateTramaAD6;
      document.getElementById("NumeroFermateTramaAD7").innerHTML =
        arr.NumeroFermateTramaAD7;
      document.getElementById("NumeroFermateTramaAD8").innerHTML =
        arr.NumeroFermateTramaAD8;
    }

    document.getElementById("NumeroTotaleFermateVarieCambioRocca").innerHTML =
      arr.NumeroTotaleFermateVarieCambioRocca;
    if (parseInt(arr.BattuteTotali) !== 0) {
      document.getElementById(
        "NumeroPerBattuteFermateVarieCambioRocca"
      ).innerHTML = (
        (100000 * arr.NumeroTotaleFermateVarieCambioRocca) /
        BattuteTotali
      ).toFixed(2);
    } else
      document.getElementById(
        "NumeroPerBattuteFermateVarieCambioRocca"
      ).innerHTML = 0;
    document.getElementById("TempoTotaleFermoVarieCambioRocca").innerHTML =
      myPrettyTimelength(arr.TempoTotaleFermoVarieCambioRocca);
    document.getElementById("NumeroTotaleFermateVarieManual").innerHTML =
      arr.NumeroTotaleFermateVarieManual;
    if (parseInt(arr.BattuteTotali) !== 0) {
      document.getElementById("NumeroPerBattuteFermateVarieManual").innerHTML =
        ((100000 * arr.NumeroTotaleFermateVarieManual) / BattuteTotali).toFixed(
          2
        );
    } else
      document.getElementById(
        "NumeroPerBattuteFermateVarieManual"
      ).innerHTML = 0;
    document.getElementById("TempoTotaleFermoVarieManual").innerHTML =
      myPrettyTimelength(arr.TempoTotaleFermoVarieManual);
    document.getElementById("NumeroTotaleFermateVarieProduzione").innerHTML =
      arr.NumeroTotaleFermateVarieProduzione;
    if (parseInt(arr.BattuteTotali) !== 0) {
      document.getElementById(
        "NumeroPerBattuteFermateVarieProduzione"
      ).innerHTML = (
        (100000 * arr.NumeroTotaleFermateVarieProduzione) /
        BattuteTotali
      ).toFixed(2);
    } else
      document.getElementById(
        "NumeroPerBattuteFermateVarieProduzione"
      ).innerHTML = 0;
    document.getElementById("TempoTotaleFermoVarieProduzione").innerHTML =
      myPrettyTimelength(arr.TempoTotaleFermoVarieProduzione);
    document.getElementById("NumeroTotaleFermateVarieGeneriche").innerHTML =
      arr.NumeroTotaleFermateVarieGeneriche;
    if (parseInt(arr.BattuteTotali) !== 0) {
      document.getElementById(
        "NumeroPerBattuteFermateVarieGeneriche"
      ).innerHTML = (
        (100000 * arr.NumeroTotaleFermateVarieGeneriche) /
        BattuteTotali
      ).toFixed(2);
    } else
      document.getElementById(
        "NumeroPerBattuteFermateVarieGeneriche"
      ).innerHTML = 0;
    document.getElementById("TempoTotaleFermoVarieGeneriche").innerHTML =
      myPrettyTimelength(arr.TempoTotaleFermoVarieGeneriche);
    if (considerAria) {
      var NumeroTotaleRipartenzeFar = parseInt(
        document.getElementById("NumeroTotaleRipartenzeFar").innerHTML
      );
      document.getElementById("NumeroTotaleFermateFar1").innerHTML =
        arr.NumeroTotaleFermateFar1;
      document.getElementById("NumeroTotaleRipartenzeFar1").innerHTML =
        arr.NumeroTotaleRipartenzeFar1;
      if (parseInt(arr.NumeroTotaleFermateFar1) !== 0) {
        document.getElementById("RapportoSuccessiFar1").innerHTML =
          (
            (NumeroTotaleRipartenzeFar / arr.NumeroTotaleFermateFar1) *
            100
          ).toFixed(2) + "%";
      } else document.getElementById("RapportoSuccessiFar1").innerHTML = 0;
      document.getElementById("NumeroTotaleFermateFar2").innerHTML =
        arr.NumeroTotaleFermateFar2;
      document.getElementById("NumeroTotaleRipartenzeFar2").innerHTML =
        arr.NumeroTotaleRipartenzeFar2;
      if (parseInt(arr.NumeroTotaleFermateFar2) !== 0) {
        document.getElementById("RapportoSuccessiFar2").innerHTML =
          (
            (NumeroTotaleRipartenzeFar / arr.NumeroTotaleFermateFar2) *
            100
          ).toFixed(2) + "%";
      } else document.getElementById("RapportoSuccessiFar2").innerHTML = 0;
      document.getElementById("NumeroTotaleFermateFar3").innerHTML =
        arr.NumeroTotaleFermateFar3;
      document.getElementById("NumeroTotaleRipartenzeFar3").innerHTML =
        arr.NumeroTotaleRipartenzeFar3;
      if (parseInt(arr.NumeroTotaleFermateFar3) !== 0) {
        document.getElementById("RapportoSuccessiFar3").innerHTML =
          (
            (NumeroTotaleRipartenzeFar / arr.NumeroTotaleFermateFar3) *
            100
          ).toFixed(2) + "%";
      } else document.getElementById("RapportoSuccessiFar3").innerHTML = 0;
      document.getElementById("NumeroTotaleFermateFar4").innerHTML =
        arr.NumeroTotaleFermateFar4;
      document.getElementById("NumeroTotaleRipartenzeFar4").innerHTML =
        arr.NumeroTotaleRipartenzeFar4;
      if (parseInt(arr.NumeroTotaleFermateFar4) !== 0) {
        document.getElementById("RapportoSuccessiFar4").innerHTML =
          (
            (NumeroTotaleRipartenzeFar / arr.NumeroTotaleFermateFar4) *
            100
          ).toFixed(2) + "%";
      } else document.getElementById("RapportoSuccessiFar4").innerHTML = 0;
      document.getElementById("NumeroTotaleFermateFar5").innerHTML =
        arr.NumeroTotaleFermateFar5;
      document.getElementById("NumeroTotaleRipartenzeFar5").innerHTML =
        arr.NumeroTotaleRipartenzeFar5;
      if (parseInt(arr.NumeroTotaleFermateFar5) !== 0) {
        document.getElementById("RapportoSuccessiFar5").innerHTML =
          (
            (NumeroTotaleRipartenzeFar / arr.NumeroTotaleFermateFar5) *
            100
          ).toFixed(2) + "%";
      } else document.getElementById("RapportoSuccessiFar5").innerHTML = 0;
      document.getElementById("NumeroTotaleFermateFar6").innerHTML =
        arr.NumeroTotaleFermateFar6;
      document.getElementById("NumeroTotaleRipartenzeFar6").innerHTML =
        arr.NumeroTotaleRipartenzeFar6;
      if (parseInt(arr.NumeroTotaleFermateFar6) !== 0) {
        document.getElementById("RapportoSuccessiFar6").innerHTML =
          (
            (NumeroTotaleRipartenzeFar / arr.NumeroTotaleFermateFar6) *
            100
          ).toFixed(2) + "%";
      } else document.getElementById("RapportoSuccessiFar6").innerHTML = 0;
      document.getElementById("NumeroTotaleFermateFar7").innerHTML =
        arr.NumeroTotaleFermateFar7;
      document.getElementById("NumeroTotaleRipartenzeFar7").innerHTML =
        arr.NumeroTotaleRipartenzeFar7;
      if (parseInt(arr.NumeroTotaleFermateFar7) !== 0) {
        document.getElementById("RapportoSuccessiFar7").innerHTML =
          (
            (NumeroTotaleRipartenzeFar / arr.NumeroTotaleFermateFar7) *
            100
          ).toFixed(2) + "%";
      } else document.getElementById("RapportoSuccessiFar7").innerHTML = 0;
      document.getElementById("NumeroTotaleFermateFar8").innerHTML =
        arr.NumeroTotaleFermateFar8;
      document.getElementById("NumeroTotaleRipartenzeFar8").innerHTML =
        arr.NumeroTotaleRipartenzeFar8;
      if (parseInt(arr.NumeroTotaleFermateFar8) !== 0) {
        document.getElementById("RapportoSuccessiFar8").innerHTML =
          (
            (NumeroTotaleRipartenzeFar / arr.NumeroTotaleFermateFar8) *
            100
          ).toFixed(2) + "%";
      } else document.getElementById("RapportoSuccessiFar8").innerHTML = 0;
    }

    document
      .getElementById("tempoTotale")
      .setAttribute("data-order", arr.TempoTotale);
    document
      .getElementById("tempoTotaleFermo")
      .setAttribute("data-order", arr.TempoFermo);
    document
      .getElementById("TempoTotaleFermoOrdito")
      .setAttribute("data-order", arr.TempoTotaleFermoOrdito);
    document
      .getElementById("TempoTotaleFermoTrama")
      .setAttribute("data-order", arr.TempoTotaleFermoTrama);
    document
      .getElementById("TempoTotaleFermoVarie")
      .setAttribute("data-order", arr.TempoTotaleFermoVarie);
    document
      .getElementById("TempoFermoOrdito1")
      .setAttribute("data-order", arr.TempoFermoOrdito1);
    document
      .getElementById("TempoFermoOrdito2")
      .setAttribute("data-order", arr.TempoFermoOrdito2);
    document
      .getElementById("TempoFermoOrdito3")
      .setAttribute("data-order", arr.TempoFermoOrdito3);
    document
      .getElementById("TempoFermoOrdito4")
      .setAttribute("data-order", arr.TempoFermoOrdito4);
    document
      .getElementById("TempoFermoCimossa1")
      .setAttribute("data-order", arr.TempoFermoCimossa1);
    document
      .getElementById("TempoFermoCimossa2")
      .setAttribute("data-order", arr.TempoFermoCimossa2);
    document
      .getElementById("TempoFermoCimossa3")
      .setAttribute("data-order", arr.TempoFermoCimossa3);
    document
      .getElementById("TempoFermoCimossa4")
      .setAttribute("data-order", arr.TempoFermoCimossa4);
    document
      .getElementById("TempoFermoSplitz1")
      .setAttribute("data-order", arr.TempoFermoSplitz1);
    document
      .getElementById("TempoFermoSplitz2")
      .setAttribute("data-order", arr.TempoFermoSplitz2);
    document
      .getElementById("TempoFermoSplitz3")
      .setAttribute("data-order", arr.TempoFermoSplitz3);
    document
      .getElementById("TempoFermoSplitz4")
      .setAttribute("data-order", arr.TempoFermoSplitz4);
    document
      .getElementById("TempoTotaleFermoTramaA")
      .setAttribute("data-order", arr.TempoTotaleFermoTramaA);
    document
      .getElementById("TempoTotaleFermoTramaP")
      .setAttribute("data-order", arr.TempoTotaleFermoTramaP);
    document
      .getElementById("TempoTotaleFermoTramaLH")
      .setAttribute("data-order", arr.TempoTotaleFermoTramaLH);
    document
      .getElementById("TempoTotaleFermoTramaE")
      .setAttribute("data-order", arr.TempoTotaleFermoTramaE);
    document
      .getElementById("TempoTotaleFermoTramaRH")
      .setAttribute("data-order", arr.TempoTotaleFermoTramaRH);
    document
      .getElementById("TempoTotaleFermoTramaTU")
      .setAttribute("data-order", arr.TempoTotaleFermoTramaTU);
    document
      .getElementById("TempoTotaleFermoTramaAD")
      .setAttribute("data-order", arr.TempoTotaleFermoTramaAD);
    document
      .getElementById("TempoTotaleFermoTramaL")
      .setAttribute("data-order", arr.TempoTotaleFermoTramaL);
    document
      .getElementById("TempoTotaleFermoTramaS")
      .setAttribute("data-order", arr.TempoTotaleFermoTramaS);
    document
      .getElementById("TempoTotaleFermoVarieCambioRocca")
      .setAttribute("data-order", arr.TempoTotaleFermoVarieCambioRocca);
    document
      .getElementById("TempoTotaleFermoVarieManual")
      .setAttribute("data-order", arr.TempoTotaleFermoVarieManual);
    document
      .getElementById("TempoTotaleFermoVarieProduzione")
      .setAttribute("data-order", arr.TempoTotaleFermoVarieProduzione);
    document
      .getElementById("TempoTotaleFermoVarieGeneriche")
      .setAttribute("data-order", arr.TempoTotaleFermoVarieGeneriche);

    document.getElementById("statisticsPanel").style.display = "";
    initializeDataTables();
  }
}

function salvaImpostazioniRicerca(
  messaggio1,
  messaggio2,
  messaggio3,
  messaggio4,
  messaggio5,
  messaggio6,
  messaggio7
) {
  var append = "";
  var selectedOptions;
  var separator = String.fromCharCode(255);

  selectedOptions = $("#telaiList option:selected");
  if (selectedOptions.length > 0) {
    var primo = true;
    for (var i = 0; i < selectedOptions.length; i++) {
      if (primo) {
        append += "&id=" + selectedOptions[i].value;
      } else {
        append += separator + selectedOptions[i].value;
      }
      primo = false;
    }
  }

  selectedOptions = $("#serialiList option:selected");
  if (selectedOptions.length > 0) {
    var primo = true;
    for (var i = 0; i < selectedOptions.length; i++) {
      if (primo) {
        append += "&serial=" + selectedOptions[i].value;
      } else {
        append += separator + selectedOptions[i].value;
      }
      primo = false;
    }
  }

  selectedOptions = $("#gruppiList option:selected");
  if (selectedOptions.length > 0) {
    var primo = true;
    for (var i = 0; i < selectedOptions.length; i++) {
      if (primo) {
        append += "&idG=" + selectedOptions[i].value;
      } else {
        append += separator + selectedOptions[i].value;
      }
      primo = false;
    }
  }

  selectedOptions = $("#saleList option:selected");
  if (selectedOptions.length > 0) {
    var primo = true;
    for (var i = 0; i < selectedOptions.length; i++) {
      if (primo) {
        append += "&idS=" + selectedOptions[i].value;
      } else {
        append += separator + selectedOptions[i].value;
      }
      primo = false;
    }
  }

  selectedOptions = $("#turniList option:selected");
  if (selectedOptions.length > 0) {
    var primo = true;
    for (var i = 0; i < selectedOptions.length; i++) {
      if (primo) {
        append += "&turno=" + selectedOptions[i].value;
      } else {
        append += separator + selectedOptions[i].value;
      }
      primo = false;
    }
  }

  selectedOptions = $("#nomiArticoloList option:selected");
  if (selectedOptions.length > 0) {
    var primo = true;
    for (var i = 0; i < selectedOptions.length; i++) {
      if (primo) {
        append += "&art=" + selectedOptions[i].value;
      } else {
        append += separator + selectedOptions[i].value;
      }
      primo = false;
    }
  }
  selectedOptions = $("#nomiDisegnoList option:selected");
  if (selectedOptions.length > 0) {
    var primo = true;
    for (var i = 0; i < selectedOptions.length; i++) {
      if (primo) {
        append += "&dis=" + encodeURIComponent(selectedOptions[i].value);
      } else {
        append += separator + encodeURIComponent(selectedOptions[i].value);
      }
      primo = false;
    }
  }

  selectedOptions = $("#nomiArmaturaList option:selected");
  if (selectedOptions.length > 0) {
    var primo = true;
    for (var i = 0; i < selectedOptions.length; i++) {
      if (primo) {
        append += "&arm=" + selectedOptions[i].value;
      } else {
        append += separator + selectedOptions[i].value;
      }
      primo = false;
    }
  }

  selectedOptions = $("#lottoOrditoListA option:selected");
  if (selectedOptions.length > 0) {
    var primo = true;
    for (var i = 0; i < selectedOptions.length; i++) {
      if (primo) {
        append += "&ordA=" + selectedOptions[i].value;
      } else {
        append += separator + selectedOptions[i].value;
      }
      primo = false;
    }
  }

  selectedOptions = $("#lottoOrditoListB option:selected");
  if (selectedOptions.length > 0) {
    var primo = true;
    for (var i = 0; i < selectedOptions.length; i++) {
      if (primo) {
        append += "&ordB=" + selectedOptions[i].value;
      } else {
        append += separator + selectedOptions[i].value;
      }
      primo = false;
    }
  }

  selectedOptions = $("#lottoTramaList option:selected");
  if (selectedOptions.length > 0) {
    var primo = true;
    for (var i = 0; i < selectedOptions.length; i++) {
      if (primo) {
        append += "&tra=" + selectedOptions[i].value;
      } else {
        append += separator + selectedOptions[i].value;
      }
      primo = false;
    }
  }

  if (document.getElementById("optionsRadios1").checked === true) {
    //-1 giorno
    append += "&time=1";
  }
  if (document.getElementById("optionsRadios2").checked === true) {
    //-1 settimana
    append += "&time=2";
  }
  if (document.getElementById("optionsRadios3").checked === true) {
    //-1 mese
    append += "&time=3";
  }
  if (document.getElementById("optionsRadios4").checked === true) {
    //by user
    if (
      $("#datetimepicker6").data("date") !== undefined &&
      $("#datetimepicker7").data("date") !== undefined
    ) {
      append +=
        "&time=4" +
        separator +
        $("#datetimepicker6").data("date") +
        separator +
        $("#datetimepicker7").data("date");
    } else {
      BootstrapDialog.alert(messaggio7);
      return;
    }
  }

  //Aggiungere criteri
  if (document.getElementById("optionsPick1").checked === true) {
    //All
    append += "&crit=1";
  }
  if (document.getElementById("optionsPick2").checked === true) {
    //Any
    append += "&crit=2";
  }
  if (document.getElementById("optionsPick3").checked === true) {
    //Same
    append += "&crit=3";
  }

  //Aggiungere Picks
  var nPicks = document.getElementById("deltaPicks").value;
  if (nPicks > 0) {
    append += "&npicks=" + nPicks;
  }

  var diag1 = new BootstrapDialog({
    title: messaggio1,
    message:
      '<input type="text" class="form-control" id="nomeQuery" placeholder="' +
      messaggio2 +
      '">',
    buttons: [
      {
        label: messaggio3,
        cssClass: "btn-primary",
        action: function (dialogItself) {
          if (document.getElementById("nomeQuery").value == "") {
            BootstrapDialog.alert(messaggio5);
            return;
          }
          selectedOptions = document.getElementById("queryPredefiniteList");
          var trovato = false;
          if (selectedOptions.children.length > 0) {
            for (var i = 0; i < selectedOptions.children.length; i++) {
              if (
                selectedOptions.children[i].text ==
                document.getElementById("nomeQuery").value
              ) {
                trovato = true;
              }
            }
          }
          var risp = true;
          if (trovato) {
            risp = confirm(messaggio6);
          }
          if (risp) {
            append +=
              "&nomequery=" + document.getElementById("nomeQuery").value;
            var xmlhttp = new XMLHttpRequest();
            var url = "./JSONmsg.php";
            xmlhttp.onreadystatechange = function () {
              if (xmlhttp.readyState === 4 && xmlhttp.status === 200) {
                getMsg(xmlhttp.responseText);
              }
            };
            xmlhttp.open("POST", url, true);
            xmlhttp.setRequestHeader(
              "Content-type",
              "application/x-www-form-urlencoded"
            );
            xmlhttp.send("type=2" + append);

            function getMsg(response) {
              document.getElementById("queryredirect").click();
            }
            dialogItself.close();
          }
        },
      },
      {
        label: messaggio4,
        cssClass: "btn-warning",
        action: function (dialogItself) {
          dialogItself.close();
        },
      },
    ],
  });
  diag1.open();
}

function eliminaImpostazioniRicerca(
  messaggio1,
  messaggio2,
  messaggio3,
  messaggio4,
  messaggio5,
  messaggio6,
  messaggio7
) {
  var idDaEliminare = $("#queryPredefiniteList option:selected")[0].value;
  if (idDaEliminare == "Default") {
    BootstrapDialog.alert(messaggio1);
    return;
  }
  BootstrapDialog.show({
    title: messaggio2,
    message: messaggio3,
    buttons: [
      {
        label: messaggio4,
        cssClass: "btn-primary",
        action: function (dialogItself) {
          var xmlhttp = new XMLHttpRequest();
          var url = "./JSONmsg.php";
          xmlhttp.onreadystatechange = function () {
            if (xmlhttp.readyState === 4 && xmlhttp.status === 200) {
              getMsg(xmlhttp.responseText);
            }
          };
          xmlhttp.open("POST", url, true);
          xmlhttp.setRequestHeader(
            "Content-type",
            "application/x-www-form-urlencoded"
          );
          xmlhttp.send("type=3&id=" + idDaEliminare);

          function getMsg(response) {
            if (response == "1") {
              BootstrapDialog.show({
                title: languageDictionary["attenzione"],
                message: messaggio6,
                buttons: [
                  {
                    label: "OK",
                    cssClass: "btn-primary",
                    action: function () {
                      window.location.href = window.location.href;
                    },
                  },
                ],
              });
            } else {
              BootstrapDialog.show({
                title: languageDictionary["attenzione"],
                message: messaggio7,
                buttons: [
                  {
                    label: "OK",
                    cssClass: "btn-primary",
                    action: function () {
                      window.location.href = window.location.href;
                    },
                  },
                ],
              });
            }
          }
          dialogItself.close();
        },
      },
      {
        label: messaggio5,
        cssClass: "btn-warning",
        action: function (dialogItself) {
          dialogItself.close();
        },
      },
    ],
  });
}

function initializeDataTables() {
  //Ordito
  var dataTable = $("#dataTables-ordito").dataTable();
  dataTable.fnDestroy();

  $.ajax({
    dataType: "json",
    url: "./JSONmsg.php",
    type: "POST",
    cache: false,
    data: "type=6",
    success: function (risposta) {
      $("#dataTables-ordito").DataTable({
        retrieve: true,
        searching: false,
        paging: false,
        info: false,
        responsive: true,
        columnDefs: [
          {
            targets: [0],
            visible: false,
          },
        ],
        language: {
          emptyTable: risposta.sEmptyTable,
          info: risposta.sInfo,
          infoEmpty: risposta.sInfoEmpty,
          infoFiltered: risposta.sInfoFiltered,
          infoPostFix: risposta.sInfoPostFix,
          thousands: risposta.sInfoThousands,
          lengthMenu: risposta.sLengthMenu,
          loadingRecords: risposta.sLoadingRecords,
          processing: risposta.sProcessing,
          search: risposta.sSearch,
          zeroRecords: risposta.sZeroRecords,
          paginate: {
            first: risposta.oPaginate.sFirst,
            last: risposta.oPaginate.sLast,
            next: risposta.oPaginate.sNext,
            previous: risposta.oPaginate.sPrevious,
          },
          aria: {
            sortAscending: risposta.sSortAscending,
            sortDescending: risposta.sDescending,
          },
        },
      });
    },
  });
  var dataTable = $("#dataTables-trama").dataTable();
  dataTable.fnDestroy();
  $.ajax({
    dataType: "json",
    url: "./JSONmsg.php",
    type: "POST",
    cache: false,
    data: "type=6",
    success: function (risposta) {
      $("#dataTables-trama").DataTable({
        retrieve: true,
        searching: false,
        paging: false,
        info: false,
        responsive: true,
        columnDefs: [
          {
            targets: [0],
            visible: false,
          },
        ],
        language: {
          emptyTable: risposta.sEmptyTable,
          info: risposta.sInfo,
          infoEmpty: risposta.sInfoEmpty,
          infoFiltered: risposta.sInfoFiltered,
          infoPostFix: risposta.sInfoPostFix,
          thousands: risposta.sInfoThousands,
          lengthMenu: risposta.sLengthMenu,
          loadingRecords: risposta.sLoadingRecords,
          processing: risposta.sProcessing,
          search: risposta.sSearch,
          zeroRecords: risposta.sZeroRecords,
          paginate: {
            first: risposta.oPaginate.sFirst,
            last: risposta.oPaginate.sLast,
            next: risposta.oPaginate.sNext,
            previous: risposta.oPaginate.sPrevious,
          },
          aria: {
            sortAscending: risposta.sSortAscending,
            sortDescending: risposta.sDescending,
          },
        },
      });
    },
  });
  var dataTable = $("#dataTables-trama1-8").dataTable();
  dataTable.fnDestroy();
  $.ajax({
    dataType: "json",
    url: "./JSONmsg.php",
    type: "POST",
    cache: false,
    data: "type=6",
    success: function (risposta) {
      $("#dataTables-trama1-8").DataTable({
        retrieve: true,
        searching: false,
        paging: false,
        info: false,
        responsive: true,
        columnDefs: [
          {
            targets: [0],
            visible: false,
          },
        ],
        language: {
          emptyTable: risposta.sEmptyTable,
          info: risposta.sInfo,
          infoEmpty: risposta.sInfoEmpty,
          infoFiltered: risposta.sInfoFiltered,
          infoPostFix: risposta.sInfoPostFix,
          thousands: risposta.sInfoThousands,
          lengthMenu: risposta.sLengthMenu,
          loadingRecords: risposta.sLoadingRecords,
          processing: risposta.sProcessing,
          search: risposta.sSearch,
          zeroRecords: risposta.sZeroRecords,
          paginate: {
            first: risposta.oPaginate.sFirst,
            last: risposta.oPaginate.sLast,
            next: risposta.oPaginate.sNext,
            previous: risposta.oPaginate.sPrevious,
          },
          aria: {
            sortAscending: risposta.sSortAscending,
            sortDescending: risposta.sDescending,
          },
        },
      });
    },
  });
  var dataTable = $("#dataTables-trama9-16").dataTable();
  dataTable.fnDestroy();
  $.ajax({
    dataType: "json",
    url: "./JSONmsg.php",
    type: "POST",
    cache: false,
    data: "type=6",
    success: function (risposta) {
      $("#dataTables-trama9-16").DataTable({
        retrieve: true,
        searching: false,
        paging: false,
        info: false,
        responsive: true,
        columnDefs: [
          {
            targets: [0],
            visible: false,
          },
        ],
        language: {
          emptyTable: risposta.sEmptyTable,
          info: risposta.sInfo,
          infoEmpty: risposta.sInfoEmpty,
          infoFiltered: risposta.sInfoFiltered,
          infoPostFix: risposta.sInfoPostFix,
          thousands: risposta.sInfoThousands,
          lengthMenu: risposta.sLengthMenu,
          loadingRecords: risposta.sLoadingRecords,
          processing: risposta.sProcessing,
          search: risposta.sSearch,
          zeroRecords: risposta.sZeroRecords,
          paginate: {
            first: risposta.oPaginate.sFirst,
            last: risposta.oPaginate.sLast,
            next: risposta.oPaginate.sNext,
            previous: risposta.oPaginate.sPrevious,
          },
          aria: {
            sortAscending: risposta.sSortAscending,
            sortDescending: risposta.sDescending,
          },
        },
      });
    },
  });
  var dataTable = $("#dataTables-varie").dataTable();
  dataTable.fnDestroy();
  $.ajax({
    dataType: "json",
    url: "./JSONmsg.php",
    type: "POST",
    cache: false,
    data: "type=6",
    success: function (risposta) {
      $("#dataTables-varie").DataTable({
        retrieve: true,
        searching: false,
        paging: false,
        info: false,
        responsive: true,
        columnDefs: [
          {
            targets: [0],
            visible: false,
          },
        ],
        language: {
          emptyTable: risposta.sEmptyTable,
          info: risposta.sInfo,
          infoEmpty: risposta.sInfoEmpty,
          infoFiltered: risposta.sInfoFiltered,
          infoPostFix: risposta.sInfoPostFix,
          thousands: risposta.sInfoThousands,
          lengthMenu: risposta.sLengthMenu,
          loadingRecords: risposta.sLoadingRecords,
          processing: risposta.sProcessing,
          search: risposta.sSearch,
          zeroRecords: risposta.sZeroRecords,
          paginate: {
            first: risposta.oPaginate.sFirst,
            last: risposta.oPaginate.sLast,
            next: risposta.oPaginate.sNext,
            previous: risposta.oPaginate.sPrevious,
          },
          aria: {
            sortAscending: risposta.sSortAscending,
            sortDescending: risposta.sDescending,
          },
        },
      });
    },
  });
  var dataTable = $("#dataTables-far").dataTable();
  dataTable.fnDestroy();
  $.ajax({
    dataType: "json",
    url: "./JSONmsg.php",
    type: "POST",
    cache: false,
    data: "type=6",
    success: function (risposta) {
      $("#dataTables-far").DataTable({
        retrieve: true,
        searching: false,
        paging: false,
        info: false,
        responsive: true,
        columnDefs: [
          {
            targets: [0],
            visible: false,
          },
        ],
        language: {
          emptyTable: risposta.sEmptyTable,
          info: risposta.sInfo,
          infoEmpty: risposta.sInfoEmpty,
          infoFiltered: risposta.sInfoFiltered,
          infoPostFix: risposta.sInfoPostFix,
          thousands: risposta.sInfoThousands,
          lengthMenu: risposta.sLengthMenu,
          loadingRecords: risposta.sLoadingRecords,
          processing: risposta.sProcessing,
          search: risposta.sSearch,
          zeroRecords: risposta.sZeroRecords,
          paginate: {
            first: risposta.oPaginate.sFirst,
            last: risposta.oPaginate.sLast,
            next: risposta.oPaginate.sNext,
            previous: risposta.oPaginate.sPrevious,
          },
          aria: {
            sortAscending: risposta.sSortAscending,
            sortDescending: risposta.sDescending,
          },
        },
      });
    },
  });
}

function resetImpostazioniRicerca(resetQueryPredefinite) {
  $("option", $("#gruppiList")).each(function () {
    $(this).removeAttr("selected").prop("selected", false);
  });
  $("#gruppiList").multiselect("refresh");

  $("option", $("#saleList")).each(function () {
    $(this).removeAttr("selected").prop("selected", false);
  });
  $("#saleList").multiselect("refresh");

  $("option", $("#serialiList")).each(function () {
    $(this).removeAttr("selected").prop("selected", false);
  });
  $("#serialiList").multiselect("refresh");

  $("option", $("#telaiList")).each(function () {
    $(this).removeAttr("selected").prop("selected", false);
  });
  $("#telaiList").multiselect("refresh");

  $("option", $("#turniList")).each(function () {
    $(this).removeAttr("selected").prop("selected", false);
  });
  $("#turniList").multiselect("refresh");

  $("option", $("#nomiArticoloList")).each(function () {
    $(this).removeAttr("selected").prop("selected", false);
  });
  $("#nomiArticoloList").multiselect("refresh");

  $("option", $("#nomiDisegnoList")).each(function () {
    $(this).removeAttr("selected").prop("selected", false);
  });
  $("#nomiDisegnoList").multiselect("refresh");

  $("option", $("#nomiArmaturaList")).each(function () {
    $(this).removeAttr("selected").prop("selected", false);
  });
  $("#nomiArmaturaList").multiselect("refresh");

  $("option", $("#lottoOrditoListA")).each(function () {
    $(this).removeAttr("selected").prop("selected", false);
  });
  $("#lottoOrditoListA").multiselect("refresh");

  $("option", $("#lottoOrditoListB")).each(function () {
    $(this).removeAttr("selected").prop("selected", false);
  });
  $("#lottoOrditoListB").multiselect("refresh");

  $("option", $("#lottoTramaList")).each(function () {
    $(this).removeAttr("selected").prop("selected", false);
  });
  $("#lottoTramaList").multiselect("refresh");

  document.getElementById("optionsRadios1").checked = true;

  //Criteri
  document.getElementById("optionsPick1").checked = true;

  $("#datetimepicker6_input").val("");
  $("#datetimepicker7_input").val("");

  if (resetQueryPredefinite === true) {
    $("option", $("#queryPredefiniteList")).each(function () {
      $(this).removeAttr("selected").prop("selected", false);
    });
    $("#queryPredefiniteList").multiselect("refresh");
    $("#queryPredefiniteList").multiselect("select", "Default");
  }
}

function getPicks() {
  $.ajax({
    type: "POST",
    url: "./JSONmsg.php",
    data: {
      type: 5040,
    },
    success: (response) => {
      document.getElementById("deltaPicks").value = response;
    },
    error: (error) => {
      console.log({ error });
    },
  });
}

function myPrettyDate(code) {
  var myDate = new Date();
  if (code === 1) myDate.setDate(myDate.getDate() - 1);
  if (code === 2) myDate.setDate(myDate.getDate() - 7);
  if (code === 3) myDate.setDate(myDate.getDate() - 30);
  var year = myDate.getFullYear();
  var month = myDate.getMonth() + 1;
  if (month <= 9) month = "0" + month;
  var day = myDate.getDate();
  if (day <= 9) day = "0" + day;
  var hours = myDate.getHours();
  if (hours <= 9) hours = "0" + hours;
  var min = myDate.getMinutes();
  if (min <= 9) min = "0" + min;
  var sec = myDate.getSeconds();
  if (sec <= 9) sec = "0" + sec;
  var prettyDate =
    year + "-" + month + "-" + day + " " + hours + ":" + min + ":" + sec;
  return prettyDate;
}

function isIdentile(string, char = null) {
  const letters = string.split("");
  const unique = new Set(letters);
  if (char && !unique.has(char)) return false;
  return unique.size === 1 ? true : false;
}

function myPrettyTimelength(secT) {
  var secondi, minuti, ore, giorni, secondi_resto, prettyTL;
  giorni = Math.floor(secT / 86400);
  secondi_resto = secT - giorni * 86400;
  ore = Math.floor(secondi_resto / 3600);
  secondi_resto = secT - giorni * 86400 - ore * 3600;
  minuti = Math.floor(secondi_resto / 60);
  secondi_resto = secT - giorni * 86400 - ore * 3600 - minuti * 60;
  secondi = secondi_resto;
  prettyTL = "";
  if (giorni > 0) prettyTL = giorni + " d ";
  if (ore > 0 || giorni > 0) prettyTL += ore + " h ";
  if (minuti > 0 || giorni > 0 || ore > 0) prettyTL += minuti + " m ";
  prettyTL += secondi + " s ";
  return prettyTL;
}

function toMinutes(sec) {
  return sec / 60;
}

function deleteTemporaryEvents() {
  document.getElementById("monitorEventsPanel").innerHTML = "";
}

function mostra_loading_screen() {
  document.getElementById("loading_screen").style.display = "block";
}

function nascondi_loading_screen() {
  document.getElementById("loading_screen").style.display = "none";
}

function convertiDensitaTrama(value) {
  value = value & 32767;
  var converted;
  if (value < 7000) {
    converted = value / 10.0;
  }
  if (value >= 7000 && value < 17000) {
    converted = (value - 7000) / 100.0;
  }
  if (value >= 17000) {
    converted = (value - 10000) / 10.0;
  }

  return converted;
}

function convertiTensioneOrdito(value) {
  return value / 10.0;
}

function convertiResa(value) {
  return value / 100.0;
}

function convertiConsumoAria(value) {
  return value / 10.0;
}

function convertiData(value) {
  return (
    value.substring(8, 10) +
    "/" +
    value.substring(5, 7) +
    "/" +
    value.substring(0, 4) +
    " " +
    value.substring(11, 19)
  );
}

function getDataFromConfiguration(param) {
  return new Promise((resolve, reject) => {
    $.ajax({
      type: "POST",
      url: "./JSONmsg.php",
      data: {
        type: 5043,
        param,
      },
      success: (response) => {
        resolve(response);
      },
      error: (error) => {
        reject(error);
      },
    });
  });
}

function cleanInnerHTML(str) {
  return str.replace(/(\r\n|\n|\r)/gm, "").trim();
}

function exportToExcel(oFilters) {
  var iex = new IntelExcel();
  var considerPinza = false;
  var considerAria = false;
  var i = 0;
  var j = 0;
  if (document.getElementById("considerPinza").value == 1) considerPinza = true;
  if (document.getElementById("considerAria").value == 1) considerAria = true;

  iex.createFile(
    cleanInnerHTML(document.getElementById("_statistiche_generali").innerHTML)
  );

  iex.write({
    cell: "A1",
    content: cleanInnerHTML(
      document.getElementById("_resa_produzione").innerHTML
    ),
  });
  iex.write({
    cell: "B1",
    content: cleanInnerHTML(document.getElementById("PercResa").innerHTML),
  });

  iex.write({
    cell: "A2",
    content: document
      .getElementById("_battute_totali")
      .innerHTML.replace(":", ""),
  });
  iex.write({
    cell: "B2",
    content: cleanInnerHTML(document.getElementById("battuteTotali").innerHTML),
  });

  iex.write({
    cell: "A3",
    content: document
      .getElementById("_lunghezza_tessuto")
      .innerHTML.replace(":", ""),
  });
  iex.write({
    cell: "B3",
    content: cleanInnerHTML(
      document.getElementById("lunghezzaTessuto").innerHTML
    ),
  });

  iex.write({
    cell: "A4",
    content: document
      .getElementById("_tempo_totale")
      .innerHTML.replace(":", ""),
  });
  iex.write({
    cell: "B4",
    content: cleanInnerHTML(document.getElementById("tempoTotale").innerHTML),
  });

  iex.write({
    cell: "A5",
    content: document
      .getElementById("_tempo_totale_fermo")
      .innerHTML.replace(":", ""),
  });
  iex.write({
    cell: "B5",
    content: cleanInnerHTML(
      document.getElementById("tempoTotaleFermo").innerHTML
    ),
  });

  iex.write({
    cell: "A7",
    content: cleanInnerHTML(document.getElementById("_stop_warp").innerHTML),
  });
  iex.write({
    cell: "B7",
    content: cleanInnerHTML(
      document.getElementById("NumeroTotaleFermateOrdito").innerHTML
    ),
  });

  iex.write({
    cell: "A8",
    content: document
      .getElementById("_su100000colpi")
      .innerHTML.replace(":", ""),
  });
  iex.write({
    cell: "B8",
    content: cleanInnerHTML(
      document.getElementById("NumeroPerBattuteFermateOrdito").innerHTML
    ),
  });

  iex.write({
    cell: "A9",
    content: cleanInnerHTML(
      document.getElementById("_SuOra").innerHTML
    ).replace(":", ""),
  });
  iex.write({
    cell: "B9",
    content: document.getElementById("NumeroPerBattuteFermateOrditoOra")
      .innerHTML,
  });

  iex.write({
    cell: "A10",
    content: document
      .getElementById("_tempo_totale")
      .innerHTML.replace(":", ""),
  });
  iex.write({
    cell: "B10",
    content: cleanInnerHTML(
      document.getElementById("TempoTotaleFermoOrdito").innerHTML
    ),
  });

  iex.write({
    cell: "A12",
    content: cleanInnerHTML(document.getElementById("_stop_weft").innerHTML),
  });
  iex.write({
    cell: "B12",
    content: cleanInnerHTML(
      document.getElementById("NumeroTotaleFermateTrama").innerHTML
    ),
  });

  iex.write({
    cell: "A13",
    content: document
      .getElementById("_su100000colpi")
      .innerHTML.replace(":", ""),
  });
  iex.write({
    cell: "B13",
    content: cleanInnerHTML(
      document.getElementById("NumeroPerBattuteFermateTrama").innerHTML
    ),
  });

  iex.write({
    cell: "A14",
    content: cleanInnerHTML(
      document.getElementById("_SuOra").innerHTML
    ).replace(":", ""),
  });
  iex.write({
    cell: "B14",
    content: document.getElementById("NumeroPerBattuteFermateTramaOra")
      .innerHTML,
  });

  iex.write({
    cell: "A15",
    content: document
      .getElementById("_tempo_totale")
      .innerHTML.replace(":", ""),
  });
  iex.write({
    cell: "B15",
    content: cleanInnerHTML(
      document.getElementById("TempoTotaleFermoTrama").innerHTML
    ),
  });

  j = 17;
  if (considerAria) {
    iex.write({
      cell: "A" + j,
      content: cleanInnerHTML(
        document.getElementById("_fermate_far").innerHTML
      ),
    });
    iex.write({
      cell: "B" + j,
      content: cleanInnerHTML(
        document.getElementById("NumeroTotaleFermateFar").innerHTML
      ),
    });
    j++;

    iex.write({
      cell: "A" + j,
      content: document
        .getElementById("_ripartenze_far")
        .innerHTML.replace(":", ""),
    });
    iex.write({
      cell: "B" + j,
      content: cleanInnerHTML(
        document.getElementById("NumeroTotaleRipartenzeFar").innerHTML
      ),
    });
    j++;

    iex.write({
      cell: "A" + j,
      content: document
        .getElementById("_rapporto_success")
        .innerHTML.replace(":", ""),
    });
    iex.write({
      cell: "B" + j,
      content: cleanInnerHTML(
        document.getElementById("RapportoSuccessiFar").innerHTML
      ),
    });
    j++;
    j++;
  }

  iex.write({
    cell: "A" + j,
    content: cleanInnerHTML(
      document.getElementById("_fermate_varie").innerHTML
    ),
  });
  iex.write({
    cell: "B" + j,
    content: cleanInnerHTML(
      document.getElementById("NumeroTotaleFermateVarie").innerHTML
    ),
  });
  j++;

  iex.write({
    cell: "A" + j,
    content: document
      .getElementById("_su100000colpi")
      .innerHTML.replace(":", ""),
  });
  iex.write({
    cell: "B" + j,
    content: cleanInnerHTML(
      document.getElementById("NumeroPerBattuteFermateVarie").innerHTML
    ),
  });
  j++;

  iex.write({
    cell: "A" + j,
    content: cleanInnerHTML(
      document.getElementById("_SuOra").innerHTML
    ).replace(":", ""),
  });
  iex.write({
    cell: "B" + j,
    content: document.getElementById("NumeroPerBattuteFermateVarieOra")
      .innerHTML,
  });
  j++;

  iex.write({
    cell: "A" + j,
    content: document
      .getElementById("_tempo_totale")
      .innerHTML.replace(":", ""),
  });
  iex.write({
    cell: "B" + j,
    content: cleanInnerHTML(
      document.getElementById("TempoTotaleFermoVarie").innerHTML
    ),
  });
  j++;

  iex.createSheet(
    cleanInnerHTML(document.getElementById("_stop_warp").innerHTML)
  );

  iex.write({
    cell: "A1",
    content: cleanInnerHTML(document.getElementById("_stop_warp").innerHTML),
  });
  iex.write({
    cell: "B1",
    content: cleanInnerHTML(document.getElementById("_fermate").innerHTML),
  });
  iex.write({ cell: "C1", content: "/100000" });
  iex.write({
    cell: "D1",
    content: cleanInnerHTML(document.getElementById("_tempo").innerHTML),
  });

  iex.write({
    cell: "A2",
    content: cleanInnerHTML(document.getElementById("_warp1").innerHTML),
  });
  iex.write({
    cell: "B2",
    content: cleanInnerHTML(
      document.getElementById("NumeroFermateOrdito1").innerHTML
    ),
  });
  iex.write({
    cell: "C2",
    content: document.getElementById("NumeroPerBattuteFermateOrdito1")
      .innerHTML,
  });
  iex.write({
    cell: "D2",
    content: cleanInnerHTML(
      document.getElementById("TempoFermoOrdito1").innerHTML
    ),
  });

  iex.write({
    cell: "A3",
    content: cleanInnerHTML(document.getElementById("_warp2").innerHTML),
  });
  iex.write({
    cell: "B3",
    content: cleanInnerHTML(
      document.getElementById("NumeroFermateOrdito2").innerHTML
    ),
  });
  iex.write({
    cell: "C3",
    content: document.getElementById("NumeroPerBattuteFermateOrdito2")
      .innerHTML,
  });
  iex.write({
    cell: "D3",
    content: cleanInnerHTML(
      document.getElementById("TempoFermoOrdito2").innerHTML
    ),
  });

  iex.write({
    cell: "A4",
    content: cleanInnerHTML(document.getElementById("_warp3").innerHTML),
  });
  iex.write({
    cell: "B4",
    content: cleanInnerHTML(
      document.getElementById("NumeroFermateOrdito3").innerHTML
    ),
  });
  iex.write({
    cell: "C4",
    content: document.getElementById("NumeroPerBattuteFermateOrdito3")
      .innerHTML,
  });
  iex.write({
    cell: "D4",
    content: cleanInnerHTML(
      document.getElementById("TempoFermoOrdito3").innerHTML
    ),
  });

  iex.write({
    cell: "A5",
    content: cleanInnerHTML(document.getElementById("_warp4").innerHTML),
  });
  iex.write({
    cell: "B5",
    content: cleanInnerHTML(
      document.getElementById("NumeroFermateOrdito4").innerHTML
    ),
  });
  iex.write({
    cell: "C5",
    content: document.getElementById("NumeroPerBattuteFermateOrdito4")
      .innerHTML,
  });
  iex.write({
    cell: "D5",
    content: cleanInnerHTML(
      document.getElementById("TempoFermoOrdito4").innerHTML
    ),
  });

  iex.write({
    cell: "A6",
    content: cleanInnerHTML(document.getElementById("_cimossa_DX").innerHTML),
  });
  iex.write({
    cell: "B6",
    content: cleanInnerHTML(
      document.getElementById("NumeroFermateCimossa4").innerHTML
    ),
  });
  iex.write({
    cell: "C6",
    content: document.getElementById("NumeroPerBattuteFermateCimossa4")
      .innerHTML,
  });
  iex.write({
    cell: "D6",
    content: cleanInnerHTML(
      document.getElementById("TempoFermoCimossa4").innerHTML
    ),
  });

  iex.write({
    cell: "A7",
    content: cleanInnerHTML(document.getElementById("_cimossa_C-DX").innerHTML),
  });
  iex.write({
    cell: "B7",
    content: cleanInnerHTML(
      document.getElementById("NumeroFermateCimossa3").innerHTML
    ),
  });
  iex.write({
    cell: "C7",
    content: document.getElementById("NumeroPerBattuteFermateCimossa3")
      .innerHTML,
  });
  iex.write({
    cell: "D7",
    content: cleanInnerHTML(
      document.getElementById("TempoFermoCimossa3").innerHTML
    ),
  });

  iex.write({
    cell: "A8",
    content: cleanInnerHTML(document.getElementById("_cimossa_C-SX").innerHTML),
  });
  iex.write({
    cell: "B8",
    content: cleanInnerHTML(
      document.getElementById("NumeroFermateCimossa2").innerHTML
    ),
  });
  iex.write({
    cell: "C8",
    content: document.getElementById("NumeroPerBattuteFermateCimossa2")
      .innerHTML,
  });
  iex.write({
    cell: "D8",
    content: cleanInnerHTML(
      document.getElementById("TempoFermoCimossa2").innerHTML
    ),
  });

  iex.write({
    cell: "A9",
    content: cleanInnerHTML(document.getElementById("_cimossa_SX").innerHTML),
  });
  iex.write({
    cell: "B9",
    content: cleanInnerHTML(
      document.getElementById("NumeroFermateCimossa1").innerHTML
    ),
  });
  iex.write({
    cell: "C9",
    content: document.getElementById("NumeroPerBattuteFermateCimossa1")
      .innerHTML,
  });
  iex.write({
    cell: "D9",
    content: cleanInnerHTML(
      document.getElementById("TempoFermoCimossa1").innerHTML
    ),
  });

  iex.write({
    cell: "A10",
    content: cleanInnerHTML(document.getElementById("_splitz_DX").innerHTML),
  });
  iex.write({
    cell: "B10",
    content: cleanInnerHTML(
      document.getElementById("NumeroFermateSplitz4").innerHTML
    ),
  });
  iex.write({
    cell: "C10",
    content: document.getElementById("NumeroPerBattuteFermateSplitz4")
      .innerHTML,
  });
  iex.write({
    cell: "D10",
    content: cleanInnerHTML(
      document.getElementById("TempoFermoSplitz4").innerHTML
    ),
  });

  iex.write({
    cell: "A11",
    content: cleanInnerHTML(document.getElementById("_splitz_C-DX").innerHTML),
  });
  iex.write({
    cell: "B11",
    content: cleanInnerHTML(
      document.getElementById("NumeroFermateSplitz3").innerHTML
    ),
  });
  iex.write({
    cell: "C11",
    content: document.getElementById("NumeroPerBattuteFermateSplitz3")
      .innerHTML,
  });
  iex.write({
    cell: "D11",
    content: cleanInnerHTML(
      document.getElementById("TempoFermoSplitz3").innerHTML
    ),
  });

  iex.write({
    cell: "A12",
    content: cleanInnerHTML(document.getElementById("_splitz_C-SX").innerHTML),
  });
  iex.write({
    cell: "B12",
    content: cleanInnerHTML(
      document.getElementById("NumeroFermateSplitz2").innerHTML
    ),
  });
  iex.write({
    cell: "C12",
    content: document.getElementById("NumeroPerBattuteFermateSplitz2")
      .innerHTML,
  });
  iex.write({
    cell: "D12",
    content: cleanInnerHTML(
      document.getElementById("TempoFermoSplitz2").innerHTML
    ),
  });

  iex.write({
    cell: "A13",
    content: cleanInnerHTML(document.getElementById("_splitz_SX").innerHTML),
  });
  iex.write({
    cell: "B13",
    content: cleanInnerHTML(
      document.getElementById("NumeroFermateSplitz1").innerHTML
    ),
  });
  iex.write({
    cell: "C13",
    content: document.getElementById("NumeroPerBattuteFermateSplitz1")
      .innerHTML,
  });
  iex.write({
    cell: "D13",
    content: cleanInnerHTML(
      document.getElementById("TempoFermoSplitz1").innerHTML
    ),
  });

  iex.createSheet(
    cleanInnerHTML(document.getElementById("_stop_weft").innerHTML)
  );

  iex.write({
    cell: "A1",
    content: cleanInnerHTML(document.getElementById("_stop_weft").innerHTML),
  });
  iex.write({
    cell: "B1",
    content: cleanInnerHTML(document.getElementById("_fermate").innerHTML),
  });
  iex.write({ cell: "C1", content: "/100000" });
  iex.write({
    cell: "D1",
    content: cleanInnerHTML(document.getElementById("_tempo").innerHTML),
  });

  i = 2;

  if (considerPinza) {
    iex.write({
      cell: "A" + i,
      content: cleanInnerHTML(document.getElementById("_anomala").innerHTML),
    });
    iex.write({
      cell: "B" + i,
      content: cleanInnerHTML(
        document.getElementById("NumeroTotaleFermateTramaA").innerHTML
      ),
    });
    iex.write({
      cell: "C" + i,
      content: document.getElementById("NumeroPerBattuteFermateTramaA")
        .innerHTML,
    });
    iex.write({
      cell: "D" + i,
      content: cleanInnerHTML(
        document.getElementById("TempoTotaleFermoTramaA").innerHTML
      ),
    });
    i++;

    iex.write({
      cell: "A" + i,
      content: cleanInnerHTML(document.getElementById("_pinzaggio").innerHTML),
    });
    iex.write({
      cell: "B" + i,
      content: cleanInnerHTML(
        document.getElementById("NumeroTotaleFermateTramaP").innerHTML
      ),
    });
    iex.write({
      cell: "C" + i,
      content: document.getElementById("NumeroPerBattuteFermateTramaP")
        .innerHTML,
    });
    iex.write({
      cell: "D" + i,
      content: cleanInnerHTML(
        document.getElementById("TempoTotaleFermoTramaP").innerHTML
      ),
    });
    i++;

    iex.write({
      cell: "A" + i,
      content: cleanInnerHTML(document.getElementById("_portante").innerHTML),
    });
    iex.write({
      cell: "B" + i,
      content: cleanInnerHTML(
        document.getElementById("NumeroTotaleFermateTramaLH").innerHTML
      ),
    });
    iex.write({
      cell: "C" + i,
      content: document.getElementById("NumeroPerBattuteFermateTramaLH")
        .innerHTML,
    });
    iex.write({
      cell: "D" + i,
      content: cleanInnerHTML(
        document.getElementById("TempoTotaleFermoTramaLH").innerHTML
      ),
    });
    i++;

    iex.write({
      cell: "A" + i,
      content: cleanInnerHTML(document.getElementById("_scambio").innerHTML),
    });
    iex.write({
      cell: "B" + i,
      content: cleanInnerHTML(
        document.getElementById("NumeroTotaleFermateTramaE").innerHTML
      ),
    });
    iex.write({
      cell: "C" + i,
      content: document.getElementById("NumeroPerBattuteFermateTramaE")
        .innerHTML,
    });
    iex.write({
      cell: "D" + i,
      content: cleanInnerHTML(
        document.getElementById("TempoTotaleFermoTramaE").innerHTML
      ),
    });
    i++;

    iex.write({
      cell: "A" + i,
      content: cleanInnerHTML(document.getElementById("_traente").innerHTML),
    });
    iex.write({
      cell: "B" + i,
      content: cleanInnerHTML(
        document.getElementById("NumeroTotaleFermateTramaRH").innerHTML
      ),
    });
    iex.write({
      cell: "C" + i,
      content: document.getElementById("NumeroPerBattuteFermateTramaRH")
        .innerHTML,
    });
    iex.write({
      cell: "D" + i,
      content: cleanInnerHTML(
        document.getElementById("TempoTotaleFermoTramaRH").innerHTML
      ),
    });
    i++;
  }

  if (considerAria) {
    iex.write({
      cell: "A" + i,
      content: cleanInnerHTML(document.getElementById("_lunga").innerHTML),
    });
    iex.write({
      cell: "B" + i,
      content: cleanInnerHTML(
        document.getElementById("NumeroTotaleFermateTramaL").innerHTML
      ),
    });
    iex.write({
      cell: "C" + i,
      content: document.getElementById("NumeroPerBattuteFermateTramaL")
        .innerHTML,
    });
    iex.write({
      cell: "D" + i,
      content: cleanInnerHTML(
        document.getElementById("TempoTotaleFermoTramaL").innerHTML
      ),
    });
    i++;

    iex.write({
      cell: "A" + i,
      content: cleanInnerHTML(document.getElementById("_corta").innerHTML),
    });
    iex.write({
      cell: "B" + i,
      content: cleanInnerHTML(
        document.getElementById("NumeroTotaleFermateTramaS").innerHTML
      ),
    });
    iex.write({
      cell: "C" + i,
      content: document.getElementById("NumeroPerBattuteFermateTramaS")
        .innerHTML,
    });
    iex.write({
      cell: "D" + i,
      content: cleanInnerHTML(
        document.getElementById("TempoTotaleFermoTramaS").innerHTML
      ),
    });
    i++;

    iex.write({
      cell: "A" + i,
      content: cleanInnerHTML(document.getElementById("_rientrata").innerHTML),
    });
    iex.write({
      cell: "B" + i,
      content: cleanInnerHTML(
        document.getElementById("NumeroTotaleFermateTramaTU").innerHTML
      ),
    });
    iex.write({
      cell: "C" + i,
      content: document.getElementById("NumeroPerBattuteFermateTramaTU")
        .innerHTML,
    });
    iex.write({
      cell: "D" + i,
      content: cleanInnerHTML(
        document.getElementById("TempoTotaleFermoTramaTU").innerHTML
      ),
    });
    i++;

    iex.write({
      cell: "A" + i,
      content: cleanInnerHTML(document.getElementById("_anticipata").innerHTML),
    });
    iex.write({
      cell: "B" + i,
      content: cleanInnerHTML(
        document.getElementById("NumeroTotaleFermateTramaAD").innerHTML
      ),
    });
    iex.write({
      cell: "C" + i,
      content: document.getElementById("NumeroPerBattuteFermateTramaTU")
        .innerHTML,
    });
    iex.write({
      cell: "D" + i,
      content: cleanInnerHTML(
        document.getElementById("TempoTotaleFermoTramaTU").innerHTML
      ),
    });
    i++;
  }

  i++;

  iex.write({
    cell: "A" + i,
    content: cleanInnerHTML(document.getElementById("_stop_weft").innerHTML),
  });
  iex.write({ cell: "B" + i, content: "1" });
  iex.write({ cell: "C" + i, content: "2" });
  iex.write({ cell: "D" + i, content: "3" });
  iex.write({ cell: "E" + i, content: "4" });
  iex.write({ cell: "F" + i, content: "5" });
  iex.write({ cell: "G" + i, content: "6" });
  iex.write({ cell: "H" + i, content: "7" });
  iex.write({ cell: "I" + i, content: "8" });
  if (considerPinza) {
    iex.write({ cell: "J" + i, content: "9" });
    iex.write({ cell: "K" + i, content: "10" });
    iex.write({ cell: "L" + i, content: "11" });
    iex.write({ cell: "M" + i, content: "12" });
    iex.write({ cell: "N" + i, content: "13" });
    iex.write({ cell: "O" + i, content: "14" });
    iex.write({ cell: "P" + i, content: "15" });
    iex.write({ cell: "Q" + i, content: "16" });
  }
  i++;

  if (considerPinza) {
    iex.write({
      cell: "A" + i,
      content: cleanInnerHTML(document.getElementById("_anomala").innerHTML),
    });
    iex.write({
      cell: "B" + i,
      content: cleanInnerHTML(
        document.getElementById("NumeroFermateTramaA1").innerHTML
      ),
    });
    iex.write({
      cell: "C" + i,
      content: cleanInnerHTML(
        document.getElementById("NumeroFermateTramaA2").innerHTML
      ),
    });
    iex.write({
      cell: "D" + i,
      content: cleanInnerHTML(
        document.getElementById("NumeroFermateTramaA3").innerHTML
      ),
    });
    iex.write({
      cell: "E" + i,
      content: cleanInnerHTML(
        document.getElementById("NumeroFermateTramaA4").innerHTML
      ),
    });
    iex.write({
      cell: "F" + i,
      content: cleanInnerHTML(
        document.getElementById("NumeroFermateTramaA5").innerHTML
      ),
    });
    iex.write({
      cell: "G" + i,
      content: cleanInnerHTML(
        document.getElementById("NumeroFermateTramaA6").innerHTML
      ),
    });
    iex.write({
      cell: "H" + i,
      content: cleanInnerHTML(
        document.getElementById("NumeroFermateTramaA7").innerHTML
      ),
    });
    iex.write({
      cell: "I" + i,
      content: cleanInnerHTML(
        document.getElementById("NumeroFermateTramaA8").innerHTML
      ),
    });
    iex.write({
      cell: "J" + i,
      content: cleanInnerHTML(
        document.getElementById("NumeroFermateTramaA9").innerHTML
      ),
    });
    iex.write({
      cell: "K" + i,
      content: cleanInnerHTML(
        document.getElementById("NumeroFermateTramaA10").innerHTML
      ),
    });
    iex.write({
      cell: "L" + i,
      content: cleanInnerHTML(
        document.getElementById("NumeroFermateTramaA11").innerHTML
      ),
    });
    iex.write({
      cell: "M" + i,
      content: cleanInnerHTML(
        document.getElementById("NumeroFermateTramaA12").innerHTML
      ),
    });
    iex.write({
      cell: "N" + i,
      content: cleanInnerHTML(
        document.getElementById("NumeroFermateTramaA13").innerHTML
      ),
    });
    iex.write({
      cell: "O" + i,
      content: cleanInnerHTML(
        document.getElementById("NumeroFermateTramaA14").innerHTML
      ),
    });
    iex.write({
      cell: "P" + i,
      content: cleanInnerHTML(
        document.getElementById("NumeroFermateTramaA15").innerHTML
      ),
    });
    iex.write({
      cell: "Q" + i,
      content: cleanInnerHTML(
        document.getElementById("NumeroFermateTramaA16").innerHTML
      ),
    });
    i++;

    iex.write({
      cell: "A" + i,
      content: cleanInnerHTML(document.getElementById("_pinzaggio").innerHTML),
    });
    iex.write({
      cell: "B" + i,
      content: cleanInnerHTML(
        document.getElementById("NumeroFermateTramaP1").innerHTML
      ),
    });
    iex.write({
      cell: "C" + i,
      content: cleanInnerHTML(
        document.getElementById("NumeroFermateTramaP2").innerHTML
      ),
    });
    iex.write({
      cell: "D" + i,
      content: cleanInnerHTML(
        document.getElementById("NumeroFermateTramaP3").innerHTML
      ),
    });
    iex.write({
      cell: "E" + i,
      content: cleanInnerHTML(
        document.getElementById("NumeroFermateTramaP4").innerHTML
      ),
    });
    iex.write({
      cell: "F" + i,
      content: cleanInnerHTML(
        document.getElementById("NumeroFermateTramaP5").innerHTML
      ),
    });
    iex.write({
      cell: "G" + i,
      content: cleanInnerHTML(
        document.getElementById("NumeroFermateTramaP6").innerHTML
      ),
    });
    iex.write({
      cell: "H" + i,
      content: cleanInnerHTML(
        document.getElementById("NumeroFermateTramaP7").innerHTML
      ),
    });
    iex.write({
      cell: "I" + i,
      content: cleanInnerHTML(
        document.getElementById("NumeroFermateTramaP8").innerHTML
      ),
    });
    iex.write({
      cell: "J" + i,
      content: cleanInnerHTML(
        document.getElementById("NumeroFermateTramaP9").innerHTML
      ),
    });
    iex.write({
      cell: "K" + i,
      content: cleanInnerHTML(
        document.getElementById("NumeroFermateTramaP10").innerHTML
      ),
    });
    iex.write({
      cell: "L" + i,
      content: cleanInnerHTML(
        document.getElementById("NumeroFermateTramaP11").innerHTML
      ),
    });
    iex.write({
      cell: "M" + i,
      content: cleanInnerHTML(
        document.getElementById("NumeroFermateTramaP12").innerHTML
      ),
    });
    iex.write({
      cell: "N" + i,
      content: cleanInnerHTML(
        document.getElementById("NumeroFermateTramaP13").innerHTML
      ),
    });
    iex.write({
      cell: "O" + i,
      content: cleanInnerHTML(
        document.getElementById("NumeroFermateTramaP14").innerHTML
      ),
    });
    iex.write({
      cell: "P" + i,
      content: cleanInnerHTML(
        document.getElementById("NumeroFermateTramaP15").innerHTML
      ),
    });
    iex.write({
      cell: "Q" + i,
      content: cleanInnerHTML(
        document.getElementById("NumeroFermateTramaP16").innerHTML
      ),
    });
    i++;

    iex.write({
      cell: "A" + i,
      content: cleanInnerHTML(document.getElementById("_portante").innerHTML),
    });
    iex.write({
      cell: "B" + i,
      content: cleanInnerHTML(
        document.getElementById("NumeroFermateTramaLH1").innerHTML
      ),
    });
    iex.write({
      cell: "C" + i,
      content: cleanInnerHTML(
        document.getElementById("NumeroFermateTramaLH2").innerHTML
      ),
    });
    iex.write({
      cell: "D" + i,
      content: cleanInnerHTML(
        document.getElementById("NumeroFermateTramaLH3").innerHTML
      ),
    });
    iex.write({
      cell: "E" + i,
      content: cleanInnerHTML(
        document.getElementById("NumeroFermateTramaLH4").innerHTML
      ),
    });
    iex.write({
      cell: "F" + i,
      content: cleanInnerHTML(
        document.getElementById("NumeroFermateTramaLH5").innerHTML
      ),
    });
    iex.write({
      cell: "G" + i,
      content: cleanInnerHTML(
        document.getElementById("NumeroFermateTramaLH6").innerHTML
      ),
    });
    iex.write({
      cell: "H" + i,
      content: cleanInnerHTML(
        document.getElementById("NumeroFermateTramaLH7").innerHTML
      ),
    });
    iex.write({
      cell: "I" + i,
      content: cleanInnerHTML(
        document.getElementById("NumeroFermateTramaLH8").innerHTML
      ),
    });
    iex.write({
      cell: "J" + i,
      content: cleanInnerHTML(
        document.getElementById("NumeroFermateTramaLH9").innerHTML
      ),
    });
    iex.write({
      cell: "K" + i,
      content: cleanInnerHTML(
        document.getElementById("NumeroFermateTramaLH10").innerHTML
      ),
    });
    iex.write({
      cell: "L" + i,
      content: cleanInnerHTML(
        document.getElementById("NumeroFermateTramaLH11").innerHTML
      ),
    });
    iex.write({
      cell: "M" + i,
      content: cleanInnerHTML(
        document.getElementById("NumeroFermateTramaLH12").innerHTML
      ),
    });
    iex.write({
      cell: "N" + i,
      content: cleanInnerHTML(
        document.getElementById("NumeroFermateTramaLH13").innerHTML
      ),
    });
    iex.write({
      cell: "O" + i,
      content: cleanInnerHTML(
        document.getElementById("NumeroFermateTramaLH14").innerHTML
      ),
    });
    iex.write({
      cell: "P" + i,
      content: cleanInnerHTML(
        document.getElementById("NumeroFermateTramaLH15").innerHTML
      ),
    });
    iex.write({
      cell: "Q" + i,
      content: cleanInnerHTML(
        document.getElementById("NumeroFermateTramaLH16").innerHTML
      ),
    });
    i++;

    iex.write({
      cell: "A" + i,
      content: cleanInnerHTML(document.getElementById("_scambio").innerHTML),
    });
    iex.write({
      cell: "B" + i,
      content: cleanInnerHTML(
        document.getElementById("NumeroFermateTramaE1").innerHTML
      ),
    });
    iex.write({
      cell: "C" + i,
      content: cleanInnerHTML(
        document.getElementById("NumeroFermateTramaE2").innerHTML
      ),
    });
    iex.write({
      cell: "D" + i,
      content: cleanInnerHTML(
        document.getElementById("NumeroFermateTramaE3").innerHTML
      ),
    });
    iex.write({
      cell: "E" + i,
      content: cleanInnerHTML(
        document.getElementById("NumeroFermateTramaE4").innerHTML
      ),
    });
    iex.write({
      cell: "F" + i,
      content: cleanInnerHTML(
        document.getElementById("NumeroFermateTramaE5").innerHTML
      ),
    });
    iex.write({
      cell: "G" + i,
      content: cleanInnerHTML(
        document.getElementById("NumeroFermateTramaE6").innerHTML
      ),
    });
    iex.write({
      cell: "H" + i,
      content: cleanInnerHTML(
        document.getElementById("NumeroFermateTramaE7").innerHTML
      ),
    });
    iex.write({
      cell: "I" + i,
      content: cleanInnerHTML(
        document.getElementById("NumeroFermateTramaE8").innerHTML
      ),
    });
    iex.write({
      cell: "J" + i,
      content: cleanInnerHTML(
        document.getElementById("NumeroFermateTramaE9").innerHTML
      ),
    });
    iex.write({
      cell: "K" + i,
      content: cleanInnerHTML(
        document.getElementById("NumeroFermateTramaE10").innerHTML
      ),
    });
    iex.write({
      cell: "L" + i,
      content: cleanInnerHTML(
        document.getElementById("NumeroFermateTramaE11").innerHTML
      ),
    });
    iex.write({
      cell: "M" + i,
      content: cleanInnerHTML(
        document.getElementById("NumeroFermateTramaE12").innerHTML
      ),
    });
    iex.write({
      cell: "N" + i,
      content: cleanInnerHTML(
        document.getElementById("NumeroFermateTramaE13").innerHTML
      ),
    });
    iex.write({
      cell: "O" + i,
      content: cleanInnerHTML(
        document.getElementById("NumeroFermateTramaE14").innerHTML
      ),
    });
    iex.write({
      cell: "P" + i,
      content: cleanInnerHTML(
        document.getElementById("NumeroFermateTramaE15").innerHTML
      ),
    });
    iex.write({
      cell: "Q" + i,
      content: cleanInnerHTML(
        document.getElementById("NumeroFermateTramaE16").innerHTML
      ),
    });
    i++;

    iex.write({
      cell: "A" + i,
      content: cleanInnerHTML(document.getElementById("_traente").innerHTML),
    });
    iex.write({
      cell: "B" + i,
      content: cleanInnerHTML(
        document.getElementById("NumeroFermateTramaRH1").innerHTML
      ),
    });
    iex.write({
      cell: "C" + i,
      content: cleanInnerHTML(
        document.getElementById("NumeroFermateTramaRH2").innerHTML
      ),
    });
    iex.write({
      cell: "D" + i,
      content: cleanInnerHTML(
        document.getElementById("NumeroFermateTramaRH3").innerHTML
      ),
    });
    iex.write({
      cell: "E" + i,
      content: cleanInnerHTML(
        document.getElementById("NumeroFermateTramaRH4").innerHTML
      ),
    });
    iex.write({
      cell: "F" + i,
      content: cleanInnerHTML(
        document.getElementById("NumeroFermateTramaRH5").innerHTML
      ),
    });
    iex.write({
      cell: "G" + i,
      content: cleanInnerHTML(
        document.getElementById("NumeroFermateTramaRH6").innerHTML
      ),
    });
    iex.write({
      cell: "H" + i,
      content: cleanInnerHTML(
        document.getElementById("NumeroFermateTramaRH7").innerHTML
      ),
    });
    iex.write({
      cell: "I" + i,
      content: cleanInnerHTML(
        document.getElementById("NumeroFermateTramaRH8").innerHTML
      ),
    });
    iex.write({
      cell: "J" + i,
      content: cleanInnerHTML(
        document.getElementById("NumeroFermateTramaRH9").innerHTML
      ),
    });
    iex.write({
      cell: "K" + i,
      content: cleanInnerHTML(
        document.getElementById("NumeroFermateTramaRH10").innerHTML
      ),
    });
    iex.write({
      cell: "L" + i,
      content: cleanInnerHTML(
        document.getElementById("NumeroFermateTramaRH11").innerHTML
      ),
    });
    iex.write({
      cell: "M" + i,
      content: cleanInnerHTML(
        document.getElementById("NumeroFermateTramaRH12").innerHTML
      ),
    });
    iex.write({
      cell: "N" + i,
      content: cleanInnerHTML(
        document.getElementById("NumeroFermateTramaRH13").innerHTML
      ),
    });
    iex.write({
      cell: "O" + i,
      content: cleanInnerHTML(
        document.getElementById("NumeroFermateTramaRH14").innerHTML
      ),
    });
    iex.write({
      cell: "P" + i,
      content: cleanInnerHTML(
        document.getElementById("NumeroFermateTramaRH15").innerHTML
      ),
    });
    iex.write({
      cell: "Q" + i,
      content: cleanInnerHTML(
        document.getElementById("NumeroFermateTramaRH16").innerHTML
      ),
    });
    i++;
  }

  if (considerAria) {
    iex.write({
      cell: "A" + i,
      content: cleanInnerHTML(document.getElementById("_lunga").innerHTML),
    });
    iex.write({
      cell: "B" + i,
      content: cleanInnerHTML(
        document.getElementById("NumeroFermateTramaL1").innerHTML
      ),
    });
    iex.write({
      cell: "C" + i,
      content: cleanInnerHTML(
        document.getElementById("NumeroFermateTramaL2").innerHTML
      ),
    });
    iex.write({
      cell: "D" + i,
      content: cleanInnerHTML(
        document.getElementById("NumeroFermateTramaL3").innerHTML
      ),
    });
    iex.write({
      cell: "E" + i,
      content: cleanInnerHTML(
        document.getElementById("NumeroFermateTramaL4").innerHTML
      ),
    });
    iex.write({
      cell: "F" + i,
      content: cleanInnerHTML(
        document.getElementById("NumeroFermateTramaL5").innerHTML
      ),
    });
    iex.write({
      cell: "G" + i,
      content: cleanInnerHTML(
        document.getElementById("NumeroFermateTramaL6").innerHTML
      ),
    });
    iex.write({
      cell: "H" + i,
      content: cleanInnerHTML(
        document.getElementById("NumeroFermateTramaL7").innerHTML
      ),
    });
    iex.write({
      cell: "I" + i,
      content: cleanInnerHTML(
        document.getElementById("NumeroFermateTramaL8").innerHTML
      ),
    });
    i++;

    iex.write({
      cell: "A" + i,
      content: cleanInnerHTML(document.getElementById("_corta").innerHTML),
    });
    iex.write({
      cell: "B" + i,
      content: cleanInnerHTML(
        document.getElementById("NumeroFermateTramaS1").innerHTML
      ),
    });
    iex.write({
      cell: "C" + i,
      content: cleanInnerHTML(
        document.getElementById("NumeroFermateTramaS2").innerHTML
      ),
    });
    iex.write({
      cell: "D" + i,
      content: cleanInnerHTML(
        document.getElementById("NumeroFermateTramaS3").innerHTML
      ),
    });
    iex.write({
      cell: "E" + i,
      content: cleanInnerHTML(
        document.getElementById("NumeroFermateTramaS4").innerHTML
      ),
    });
    iex.write({
      cell: "F" + i,
      content: cleanInnerHTML(
        document.getElementById("NumeroFermateTramaS5").innerHTML
      ),
    });
    iex.write({
      cell: "G" + i,
      content: cleanInnerHTML(
        document.getElementById("NumeroFermateTramaS6").innerHTML
      ),
    });
    iex.write({
      cell: "H" + i,
      content: cleanInnerHTML(
        document.getElementById("NumeroFermateTramaS7").innerHTML
      ),
    });
    iex.write({
      cell: "I" + i,
      content: cleanInnerHTML(
        document.getElementById("NumeroFermateTramaS8").innerHTML
      ),
    });
    i++;

    iex.write({
      cell: "A" + i,
      content: cleanInnerHTML(document.getElementById("_rientrata").innerHTML),
    });
    iex.write({
      cell: "B" + i,
      content: cleanInnerHTML(
        document.getElementById("NumeroFermateTramaTU1").innerHTML
      ),
    });
    iex.write({
      cell: "C" + i,
      content: cleanInnerHTML(
        document.getElementById("NumeroFermateTramaTU2").innerHTML
      ),
    });
    iex.write({
      cell: "D" + i,
      content: cleanInnerHTML(
        document.getElementById("NumeroFermateTramaTU3").innerHTML
      ),
    });
    iex.write({
      cell: "E" + i,
      content: cleanInnerHTML(
        document.getElementById("NumeroFermateTramaTU4").innerHTML
      ),
    });
    iex.write({
      cell: "F" + i,
      content: cleanInnerHTML(
        document.getElementById("NumeroFermateTramaTU5").innerHTML
      ),
    });
    iex.write({
      cell: "G" + i,
      content: cleanInnerHTML(
        document.getElementById("NumeroFermateTramaTU6").innerHTML
      ),
    });
    iex.write({
      cell: "H" + i,
      content: cleanInnerHTML(
        document.getElementById("NumeroFermateTramaTU7").innerHTML
      ),
    });
    iex.write({
      cell: "I" + i,
      content: cleanInnerHTML(
        document.getElementById("NumeroFermateTramaTU8").innerHTML
      ),
    });
    i++;

    iex.write({
      cell: "A" + i,
      content: cleanInnerHTML(document.getElementById("_anticipata").innerHTML),
    });
    iex.write({
      cell: "B" + i,
      content: cleanInnerHTML(
        document.getElementById("NumeroFermateTramaAD1").innerHTML
      ),
    });
    iex.write({
      cell: "C" + i,
      content: cleanInnerHTML(
        document.getElementById("NumeroFermateTramaAD2").innerHTML
      ),
    });
    iex.write({
      cell: "D" + i,
      content: cleanInnerHTML(
        document.getElementById("NumeroFermateTramaAD3").innerHTML
      ),
    });
    iex.write({
      cell: "E" + i,
      content: cleanInnerHTML(
        document.getElementById("NumeroFermateTramaAD4").innerHTML
      ),
    });
    iex.write({
      cell: "F" + i,
      content: cleanInnerHTML(
        document.getElementById("NumeroFermateTramaAD5").innerHTML
      ),
    });
    iex.write({
      cell: "G" + i,
      content: cleanInnerHTML(
        document.getElementById("NumeroFermateTramaAD6").innerHTML
      ),
    });
    iex.write({
      cell: "H" + i,
      content: cleanInnerHTML(
        document.getElementById("NumeroFermateTramaAD7").innerHTML
      ),
    });
    iex.write({
      cell: "I" + i,
      content: cleanInnerHTML(
        document.getElementById("NumeroFermateTramaAD8").innerHTML
      ),
    });
    i++;
  }

  if (considerAria) {
    iex.createSheet(
      cleanInnerHTML(document.getElementById("_fermate_far").innerHTML)
    );

    iex.write({
      cell: "A1",
      content: cleanInnerHTML(
        document.getElementById("_fermate_far").innerHTML
      ),
    });
    iex.write({ cell: "B1", content: "1" });
    iex.write({ cell: "C1", content: "2" });
    iex.write({ cell: "D1", content: "3" });
    iex.write({ cell: "E1", content: "4" });
    iex.write({ cell: "F1", content: "5" });
    iex.write({ cell: "G1", content: "6" });
    iex.write({ cell: "H1", content: "7" });
    iex.write({ cell: "I1", content: "8" });

    iex.write({
      cell: "A2",
      content: cleanInnerHTML(document.getElementById("_fermate").innerHTML),
    });
    iex.write({
      cell: "B2",
      content: cleanInnerHTML(
        document.getElementById("NumeroTotaleFermateFar1").innerHTML
      ),
    });
    iex.write({
      cell: "C2",
      content: cleanInnerHTML(
        document.getElementById("NumeroTotaleFermateFar2").innerHTML
      ),
    });
    iex.write({
      cell: "D2",
      content: cleanInnerHTML(
        document.getElementById("NumeroTotaleFermateFar3").innerHTML
      ),
    });
    iex.write({
      cell: "E2",
      content: cleanInnerHTML(
        document.getElementById("NumeroTotaleFermateFar4").innerHTML
      ),
    });
    iex.write({
      cell: "F2",
      content: cleanInnerHTML(
        document.getElementById("NumeroTotaleFermateFar5").innerHTML
      ),
    });
    iex.write({
      cell: "G2",
      content: cleanInnerHTML(
        document.getElementById("NumeroTotaleFermateFar6").innerHTML
      ),
    });
    iex.write({
      cell: "H2",
      content: cleanInnerHTML(
        document.getElementById("NumeroTotaleFermateFar7").innerHTML
      ),
    });
    iex.write({
      cell: "I2",
      content: cleanInnerHTML(
        document.getElementById("NumeroTotaleFermateFar8").innerHTML
      ),
    });

    iex.write({
      cell: "A3",
      content: cleanInnerHTML(document.getElementById("_ripartenze").innerHTML),
    });
    iex.write({
      cell: "B3",
      content: cleanInnerHTML(
        document.getElementById("NumeroTotaleRipartenzeFar1").innerHTML
      ),
    });
    iex.write({
      cell: "C3",
      content: cleanInnerHTML(
        document.getElementById("NumeroTotaleRipartenzeFar2").innerHTML
      ),
    });
    iex.write({
      cell: "D3",
      content: cleanInnerHTML(
        document.getElementById("NumeroTotaleRipartenzeFar3").innerHTML
      ),
    });
    iex.write({
      cell: "E3",
      content: cleanInnerHTML(
        document.getElementById("NumeroTotaleRipartenzeFar4").innerHTML
      ),
    });
    iex.write({
      cell: "F3",
      content: cleanInnerHTML(
        document.getElementById("NumeroTotaleRipartenzeFar5").innerHTML
      ),
    });
    iex.write({
      cell: "G3",
      content: cleanInnerHTML(
        document.getElementById("NumeroTotaleRipartenzeFar6").innerHTML
      ),
    });
    iex.write({
      cell: "H3",
      content: cleanInnerHTML(
        document.getElementById("NumeroTotaleRipartenzeFar7").innerHTML
      ),
    });
    iex.write({
      cell: "I3",
      content: cleanInnerHTML(
        document.getElementById("NumeroTotaleRipartenzeFar8").innerHTML
      ),
    });

    iex.write({
      cell: "A4",
      content: document
        .getElementById("_rapporto_success")
        .innerHTML.replace(":", ""),
    });
    iex.write({
      cell: "B4",
      content: cleanInnerHTML(
        document.getElementById("RapportoSuccessiFar1").innerHTML
      ),
    });
    iex.write({
      cell: "C4",
      content: cleanInnerHTML(
        document.getElementById("RapportoSuccessiFar2").innerHTML
      ),
    });
    iex.write({
      cell: "D4",
      content: cleanInnerHTML(
        document.getElementById("RapportoSuccessiFar3").innerHTML
      ),
    });
    iex.write({
      cell: "E4",
      content: cleanInnerHTML(
        document.getElementById("RapportoSuccessiFar4").innerHTML
      ),
    });
    iex.write({
      cell: "F4",
      content: cleanInnerHTML(
        document.getElementById("RapportoSuccessiFar5").innerHTML
      ),
    });
    iex.write({
      cell: "G4",
      content: cleanInnerHTML(
        document.getElementById("RapportoSuccessiFar6").innerHTML
      ),
    });
    iex.write({
      cell: "H4",
      content: cleanInnerHTML(
        document.getElementById("RapportoSuccessiFar7").innerHTML
      ),
    });
    iex.write({
      cell: "I4",
      content: cleanInnerHTML(
        document.getElementById("RapportoSuccessiFar8").innerHTML
      ),
    });
  }

  iex.createSheet(
    cleanInnerHTML(document.getElementById("_fermate_varie").innerHTML)
  );

  iex.write({
    cell: "A1",
    content: cleanInnerHTML(
      document.getElementById("_fermate_varie").innerHTML
    ),
  });
  iex.write({
    cell: "B1",
    content: cleanInnerHTML(document.getElementById("_fermate").innerHTML),
  });
  iex.write({ cell: "C1", content: "/100000" });
  iex.write({
    cell: "D1",
    content: cleanInnerHTML(document.getElementById("_tempo").innerHTML),
  });

  iex.write({
    cell: "A2",
    content: cleanInnerHTML(
      document.getElementById("_stop_cone_change").innerHTML
    ),
  });
  iex.write({
    cell: "B2",
    content: document.getElementById("NumeroTotaleFermateVarieCambioRocca")
      .innerHTML,
  });
  iex.write({
    cell: "C2",
    content: document.getElementById("NumeroPerBattuteFermateVarieCambioRocca")
      .innerHTML,
  });
  iex.write({
    cell: "D2",
    content: document.getElementById("TempoTotaleFermoVarieCambioRocca")
      .innerHTML,
  });

  iex.write({
    cell: "A3",
    content: cleanInnerHTML(
      document.getElementById("_stop_prod_end").innerHTML
    ),
  });
  iex.write({
    cell: "B3",
    content: document.getElementById("NumeroTotaleFermateVarieProduzione")
      .innerHTML,
  });
  iex.write({
    cell: "C3",
    content: document.getElementById("NumeroPerBattuteFermateVarieProduzione")
      .innerHTML,
  });
  iex.write({
    cell: "D3",
    content: document.getElementById("TempoTotaleFermoVarieProduzione")
      .innerHTML,
  });

  iex.write({
    cell: "A4",
    content: cleanInnerHTML(document.getElementById("_stop_manual").innerHTML),
  });
  iex.write({
    cell: "B4",
    content: document.getElementById("NumeroTotaleFermateVarieManual")
      .innerHTML,
  });
  iex.write({
    cell: "C4",
    content: document.getElementById("NumeroPerBattuteFermateVarieManual")
      .innerHTML,
  });
  iex.write({
    cell: "D4",
    content: cleanInnerHTML(
      document.getElementById("TempoTotaleFermoVarieManual").innerHTML
    ),
  });

  iex.write({
    cell: "A5",
    content: cleanInnerHTML(document.getElementById("_stop_generic").innerHTML),
  });
  iex.write({
    cell: "B5",
    content: document.getElementById("NumeroTotaleFermateVarieGeneriche")
      .innerHTML,
  });
  iex.write({
    cell: "C5",
    content: document.getElementById("NumeroPerBattuteFermateVarieGeneriche")
      .innerHTML,
  });
  iex.write({
    cell: "D5",
    content: document.getElementById("TempoTotaleFermoVarieGeneriche")
      .innerHTML,
  });

  iex.createSheet(oFilters.title);

  iex.write({ cell: "A1", content: oFilters.filters.numeroTelaio.title });
  iex.write({ cell: "B1", content: oFilters.filters.numeroTelaio.value });
  iex.write({ cell: "A2", content: oFilters.filters.gruppi.title });
  iex.write({ cell: "B2", content: oFilters.filters.gruppi.value });
  iex.write({ cell: "A3", content: oFilters.filters.sale.title });
  iex.write({ cell: "B3", content: oFilters.filters.sale.value });
  iex.write({ cell: "A4", content: oFilters.filters.articoli.title });
  iex.write({ cell: "B4", content: oFilters.filters.articoli.value });
  iex.write({ cell: "A5", content: oFilters.filters.armature.title });
  iex.write({ cell: "B5", content: oFilters.filters.armature.value });
  iex.write({ cell: "A6", content: oFilters.filters.noteColore.title });
  iex.write({ cell: "B6", content: oFilters.filters.noteColore.value });
  iex.write({ cell: "A7", content: oFilters.filters.turni.title });
  iex.write({ cell: "B7", content: oFilters.filters.turni.value });
  iex.write({ cell: "A8", content: oFilters.filters.gen11.title });
  iex.write({ cell: "B8", content: oFilters.filters.gen11.value });
  iex.write({ cell: "A9", content: oFilters.filters.gen22.title });
  iex.write({ cell: "B9", content: oFilters.filters.gen22.value });
  iex.write({ cell: "A10", content: oFilters.filters.gen33.title });
  iex.write({ cell: "B10", content: oFilters.filters.gen33.value });
  iex.write({ cell: "A11", content: oFilters.filters.periodo.title });
  iex.write({ cell: "B11", content: oFilters.filters.periodo.value });
  iex.write({ cell: "A12", content: oFilters.filters.criterio.title });
  iex.write({ cell: "B12", content: oFilters.filters.criterio.value });
  iex.write({ cell: "A13", content: oFilters.filters.limite.title });
  iex.write({ cell: "B13", content: oFilters.filters.limite.value });
  let date = new Date();
  iex.saveAs(
    `${pageName}_${new Date(
      date.getTime() - date.getTimezoneOffset() * 60000
    ).toISOString()}.xlsx`
  );
}
