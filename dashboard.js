function openTab(tablinkId, tabcontentID, subtabcontentID) {
    var i, tabcontent, tablinks;
    tabcontent = document.getElementsByClassName('tabcontent');
    for (i = 0; i < tabcontent.length; i++) {
        tabcontent[i].style.display = "none";
    }
    subtabcontent = document.getElementsByClassName('subtabcontent');
    for (i = 0; i < subtabcontent.length; i++) {
        subtabcontent[i].style.display = "none";
    }
    tablinks = document.getElementsByClassName("tablinks");
    for (i = 0; i < tablinks.length; i++) {
        tablinks[i].classList.remove("active");
    }
    document.getElementById(tabcontentID).style.display = "block";
    document.getElementById(tablinkId).classList.add("active");
    if (subtabcontentID) {
        document.getElementById(subtabcontentID).style.display = 'block';
    }
    localStorage.setItem("tab", tablinkId);
    localStorage.setItem("subtab", subtabcontentID || "");
}

function viewScorecard(project) {
    let scorecardResult = $('#scorecard-result');
    //scorecardResult.html(`<object data="scorecard/${project}.html" type="text/html" style="width:100%;height:500px;"></object>`);
    scorecardResult.load(`scorecard/${project}.html #scorecard-result-replace-div`);
    localStorage.setItem("project", project);
    openTab('scorecardHistoryNav', 'scorecard-history', 'scorecard-result');
}

function viewScorecardInNewTab(project) {
    window.open(`scorecard/${project}.html`, '_blank');
}

function restoreTab() {
    var tab = localStorage.getItem("tab");
    var subtab = localStorage.getItem("subtab");
    var project = localStorage.getItem('project');
    if (tab) {
        document.getElementById(tab).click();
    }
    if(subtab == 'scorecard-result') {
        viewScorecard(project);
    }
}

var activeElement = {};

$(document).on('click', '.table-row td', function (e) {
    if ($(this).attr('onclick')) {
        currentActiveElement = $(this);
        if (currentActiveElement.is('td')) {
            if (activeElement.length > 0 && activeElement[0] !== currentActiveElement[0]) {
                activeElement.removeClass('active');
            }
            activeElement = currentActiveElement;
            activeElement.addClass('active');
        }
    }
});

function closeRemarkDialog() {
    activeElement.removeClass('active');
    document.getElementById("remarkDialog").style.display = "none";
}

function showRemark(remark) {
    document.querySelector('#remarkDialogContent').textContent = remark;
    document.getElementById("remarkDialog").style.display = "block";
    const dialog = document.getElementById("remarkDialog");
    dialog.style.display = "block";
}

$(document).ready(function() {
    window.onscroll = function() {
        var topbar = document.getElementById("topbar");
        if (window.pageYOffset > 0) {
            topbar.classList.add("sticky");
        } else {
            topbar.classList.remove("sticky");
        }
    };

    function clearServerSession() {
        $.post('authentication/login.php', { action: 'logout' }, function(response) {
            if (response.status == 'success') {
                window.location.href = response.url;
            }
        });
    }

    function populateDcList() {
        $.post('cluster/cluster_management.php', { action: 'getDCList' }, function(data) {
            let select = $('#dc_select');
            select.empty();
            if (data.success) {
                data.dcList.forEach(function(dc) {
                    select.append('<option value="' + dc + '">' + dc + '</option>');
                });
            } else {
                let errorMessage = "The following errors occurred: ";
                data.errors.forEach(function(error) {
                    errorMessage += error.message + ". ";
                });
                select.append('<option value="">Error Fetching Project List</option>');
                alert(errorMessage);
            }
        });
    }

    function populateTypeList() {
        $.post('cluster/cluster_management.php', { action: 'getTypeList' }, function(data) {
            let select = $('#type_select');
            select.empty();
            if (data.success) {
                data.typeList.forEach(function(type) {
                    select.append('<option value="' + type + '">' + type + '</option>');
                });
            } else {
                let errorMessage = "The following errors occurred: ";
                data.errors.forEach(function(error) {
                    errorMessage += error.message + ". ";
                });
                select.append('<option value="">Error Fetching Project List</option>');
                alert(errorMessage);
            }
        });
    }

    function populateClusterList() {
        $.post('cluster/cluster_management.php', { action: 'getClusterList' }, function(data) {
            let tbody = $('#cluster_list tbody');
            tbody.empty();
            if (data.success) {
                data.clusterList.forEach(function(cluster) {
                    tbody.append('<tr class="table-row"><td class="table-data">' + cluster.dc + '</td><td class="table-data">' + cluster.typeName + '</td><td class="table-data">' + cluster.cluster + '</td></tr>');
                });
            } else {
                let errorMessage = "The following errors occurred: ";
                data.errors.forEach(function(error) {
                    errorMessage += error.message + ". ";
                });
                alert(errorMessage);
            }
        });
    }

    function populateProjectList() {
        $.post('project/project_list.php', { action: 'populateProjectList' }, function(data) {
            let select = $('#project_select');
            select.empty();
            if (data.success) {
                data.projectList.forEach(function(project) {
                    select.append('<option value="' + project + '">' + project + '</option>');
                });
            } else {
                let errorMessage = "The following errors occurred: ";
                data.errors.forEach(function(error) {
                    errorMessage += error.message + ". ";
                });
                select.append('<option value="">Error Fetching Project List</option>');
                alert(errorMessage);
            }
            projectChange();
        });
    }

    function populateProjectScorecardRecord() {
        $.post('project/project_list.php', { action: 'populateProjectScorecardRecord' }, function(data) {
            let tbody = $('#scorecard-history-table tbody');
            tbody.empty();
            if (data.success) {
                data.projectScorecardRecord.forEach(function(project) {
                    tbody.append('<tr class="table-row"><td class="table-data">' + project.creationDate + '</td><td class="table-data">' + project.project + '</td><td class="table-data"><button class="button" onclick="viewScorecard(\'' + project.project + '\')">View</button><button class="button" onclick="viewScorecardInNewTab(\'' + project.project + '\')">View in New Tab</button></td></tr>');
                });
            } else {
                let errorMessage = "The following errors occurred: ";
                data.errors.forEach(function(error) {
                    errorMessage += error.message + ". ";
                });
                select.append('<option value="">Error Fetching Project List</option>');
                alert(errorMessage);
            }
        });
    }

    function projectChange() {
        if ($('#project_select').val() !== '') {
            $('#generate_scorecard_button').prop('disabled', false);
        } else {
            $('#generate_scorecard_button').prop('disabled', true);
        }
    }

    function updateProjectList() {
        const $updateProjectListButton = $('#update_project_list');
        $updateProjectListButton.prop('disabled', true);
        const originalText = $('#update_project_list').text();
        $updateProjectListButton.text('60...');
        $('#project_select').prop('disabled', true);
        $('#generate_scorecard_button').prop('disabled', true);

        const updateInterval = setInterval(() => {
            if ($updateProjectListButton.text() !== originalText) {
                const currentCount = parseInt($updateProjectListButton.text().match(/\d+/)[0]);
                if (currentCount === 1) {
                    clearInterval(updateInterval);
                    $updateProjectListButton.text('Updating');
                } else {
                    $updateProjectListButton.text(currentCount - 1 + '...');
                }
            }
        }, 1000);

        $.post('project/project_list.php', { action: 'updateProjectList' }, function(data) {
            $updateProjectListButton.text(originalText);
            $updateProjectListButton.prop('disabled', false);
            $('#project_select').prop('disabled', false);
            if (!data.success) {
                let errorMessage = "The following errors occurred: ";
                data.errors.forEach(function(error) {
                    if (error.cluster) {
                        errorMessage += "Cluster " + error.cluster + ": " + error.message + ". ";
                    } else {
                        errorMessage += error.message + ". ";
                    }
                });
                alert(errorMessage);
            } else {
                populateProjectList();
            }
        }, 'json');
    }

    function getColumnIndex(headers, sortBy) {
        for (let i = 0; i < headers.length; i++) {
            if (headers[i].getAttribute('data-sort-by') === sortBy) {
                return i;
            }
        }
        return -1;
    }

    function sortTableRows(table, headers, sortBy, ascending = true) {
        const rows = table.rows;
        const sortedRows = Array.from(rows).slice(1).sort((a, b) => {
            const cellA = a.cells[getColumnIndex(headers, sortBy)];
            const cellB = b.cells[getColumnIndex(headers, sortBy)];
            const valueA = sortBy === 'creation-date' ? (cellA.innerText ? new Date(cellA.innerText) : null) : cellA.innerText;
            const valueB = sortBy === 'creation-date' ? (cellB.innerText ? new Date(cellB.innerText) : null) : cellB.innerText;

            if (ascending) {
                if (valueA === null && valueB === null) {
                    return 0;
                } else if (valueA === null) {
                    return 1;
               } else if (valueB === null) {
                    return -1;
                } else {
                    return valueA > valueB ? 1 : -1;
                }
            } else {
                if (valueA === null && valueB === null) {
                    return 0;
                } else if (valueA === null) {
                    return -1;
                } else if (valueB === null) {
                    return 1;
                } else {
                    return valueB > valueA ? 1 : -1;
                }
            }
        });

        const tbody = table.querySelector('tbody');
        tbody.innerHTML = '';
        sortedRows.forEach(row => tbody.appendChild(row));
    }

    const scorecardTable = document.querySelector('#scorecard-history-table');
    const scorecardHeaders = scorecardTable.querySelectorAll('.sortable');
    const clusterTable = document.querySelector('#cluster_list');
    const clusterHeaders = clusterTable.querySelectorAll('.sortable');


    scorecardHeaders.forEach(header => {
        header.addEventListener('click', () => {
            const sortBy = header.getAttribute('data-sort-by');
            const ascending = !header.classList.contains('ascending') || header.classList.contains('descending');

            scorecardHeaders.forEach(h => h.classList.remove('ascending', 'descending'));
            if (ascending) {
                header.classList.add('ascending');
            } else {
                header.classList.add('descending');
            }

            sortTableRows(scorecardTable, scorecardHeaders, sortBy, ascending);
        });
    });

    clusterHeaders.forEach(header => {
        header.addEventListener('click', () => {
            const sortBy = header.getAttribute('data-sort-by');
            const ascending = !header.classList.contains('ascending') || header.classList.contains('descending');

            clusterHeaders.forEach(h => h.classList.remove('ascending', 'descending'));
            if (ascending) {
                header.classList.add('ascending');
            } else {
                header.classList.add('descending');
            }

            sortTableRows(clusterTable, clusterHeaders, sortBy, ascending);
        });
    });

    function verifySession() {
        $.post('authentication/login.php', { action: 'verifySession' }, function(response) {
            if (response.status == 'success') {
                if (localStorage.getItem('username') == response.data['username'] && localStorage.getItem('role') == response.data['role']) {
                    const role = localStorage.getItem('role');
                    const elementsToDisable = role === 'view' ? document.getElementsByClassName('not_for_view') : document.getElementsByClassName('not_for_admin');
                    for (const element of elementsToDisable) {
                        if (element.disabled)
                            continue;
                        else
                            element.disabled = true;
                            element.style.display = 'none';
                    }
                    populateProjectList();
                    populateDcList();
                    populateTypeList();
                    populateClusterList();
                    populateProjectScorecardRecord();
                } else {
                    localStorage.clear();
                    clearServerSession();
                }
            } else {
                localStorage.clear();
                window.location.href = response.url;
            }
        }, 'json');
    }

    function addDC() {
        if (localStorage.getItem('role') == 'view') {
            verifySession();
            window.location.href = '';
            return;
        }
        let dc = $('#dc_input').val();
        $.post('cluster/cluster_management.php', { action: 'addDC', dc: dc }, function(data){
            if (!data.success) {
                alert(data.error);
            } else {
                $('#dc_input').val('');
                populateDcList();
            }
        }, 'json');
    }

    function addCluster() {
        if (localStorage.getItem('role') == 'view') {
            verifySession();
            window.location.href = '';
            return;
        }
        let dc = $('#dc_select').val();
        let type = $('#type_select').val();
        let cluster = $('#cluster_input').val();
        $.post('cluster/cluster_management.php', { action: 'addCluster', dc: dc, type: type, cluster: cluster }, function(data){
            if (!data.success) {
                alert(data.error);
            } else {
                $('#cluster_input').val('');
                populateClusterList();
            }
        }, 'json');
    }

    function generateScorecard() {
        $('#generate_scorecard_button').prop('disabled', true);
        $('#project_select').prop('disabled', true);
        $('#update_project_list').prop('disabled', true);
        let project = $('#project_select').val();
        $.post('project/project_scorecard.php', { action: 'generateScorecard', project: project}, function(data) {
            $('#generate_scorecard_button').prop('disabled', false);
            $('#project_select').prop('disabled', false);
            $('#update_project_list').prop('disabled', false);
            if (data == "../scorecard/" + project + ".html") {
                populateProjectScorecardRecord();
                openTab('scorecardHistoryNav', 'scorecard-history', 'scorecard-list');
            } else {
                alert(data);
            }
        });
    }

    function generate2ndgenScorecard() {
        $('#generate_2ndgen_scorecard_button').prop('disabled', true);
        $('#project_select').prop('disabled', true);
        $('#update_project_list').prop('disabled', true);
        let project = $('#project_select').val();
        $.post('project/dev_project_scorecard.php', { action: 'generateScorecard', project: project}, function(data) {
            $('#generate_2ndgen_scorecard_button').prop('disabled', false);
            $('#project_select').prop('disabled', false);
            $('#update_project_list').prop('disabled', false);
            if (data == "../scorecard/" + project + ".html") {
                populateProjectScorecardRecord();
                openTab('scorecardHistoryNav', 'scorecard-history', 'scorecard-list');
            } else {
                alert(data);
            }
        });
    }
    
    $('#update_project_list').on('click', function() {
        updateProjectList();
    });

    $('#project_select').on('change', function() {
        projectChange();
    });

    $('#generate_scorecard_form').on('submit', function(e) {
        e.preventDefault();
        generateScorecard();
    });

    $('#generate_2ndgen_scorecard_button').on('click', function(e) {
        e.preventDefault();
        generate2ndgenScorecard();
    });

    $('#add_dc_form').on('submit', function(e) {
        e.preventDefault();
        addDC();
    });

    $('#add_cluster_form').on('submit', function(e) {
        e.preventDefault();
        addCluster();
    });
    
    $('#logout-button').click(function(e) {
        e.preventDefault();
        localStorage.clear();
        clearServerSession();
    });

    verifySession();
});