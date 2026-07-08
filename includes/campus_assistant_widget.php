<?php

function render_campus_assistant_widget(): void
{
    ?>
    <div class="assistant-fab" onclick="toggleCampusAssistant()">
        ?
    </div>

    <div class="assistant-panel" id="campusAssistantPanel">
        <div class="assistant-head">
            <strong>Need help?</strong>
            <button type="button" onclick="toggleCampusAssistant()">×</button>
        </div>

        <p>What would you like to report?</p>

        <div class="assistant-options">
            <button type="button" onclick="fillAssistantProblem('WiFi or computer not working')">
                WiFi or computer issue
            </button>

            <button type="button" onclick="fillAssistantProblem('Attendance marked wrong')">
                Attendance issue
            </button>

            <button type="button" onclick="fillAssistantProblem('Fee clearance or payment issue')">
                Fee clearance issue
            </button>

            <button type="button" onclick="fillAssistantProblem('Food or cafe issue')">
                Food or cafe issue
            </button>

            <button type="button" onclick="fillAssistantProblem('Classroom occupied or unavailable')">
                Classroom issue
            </button>
        </div>

        <small>
            Choose a common issue to start your report.
        </small>
    </div>

    <script>
    function toggleCampusAssistant() {
        const panel = document.getElementById('campusAssistantPanel');
        if (!panel) return;
        panel.classList.toggle('open');
    }

    function fillAssistantProblem(text) {
        const title = document.querySelector('input[name="title"]');
        const description = document.querySelector('textarea[name="description"]');

        if (title && !title.value) {
            title.value = text;
        }

        if (description && !description.value) {
            description.value = 'Issue: ' + text;
        }

        toggleCampusAssistant();
    }
    </script>
    <?php
}
