'use strict';

$(document).ready(function () {
    if ($('#sessions_tab').length && window.sessionsData) {
        initializeSessionsVisualization();
    }
});

function initializeSessionsVisualization() {
    const data = window.sessionsData;
    renderDaysTimeline(data.days);
}

function renderDaysTimeline(days) {
    const container = $('#sessions-timeline-container');
    container.empty();

    if (days.length === 0) {
        container.html(
            '<div class="text-center py-5">' +
            '<i class="ti ti-calendar-off" style="font-size: 2rem; color: #ddd;"></i>' +
            '<p class="text-muted mt-2 mb-0" style="font-size: 0.75rem;">لا توجد جلسات عمل في هذا الشهر</p>' +
            '</div>'
        );
        return;
    }

    days.forEach((day, index) => {
        container.append(createDayRow(day, index));
    });

    // تفعيل tooltips
    $('[data-bs-toggle="tooltip"]').tooltip({ html: true });
}

function createDayRow(day, index) {
    const colorGradient = getColorGradient(index, 30);
    let timelineHtml = '';
    let timeAxisHtml = '';

    // استخراج الفترات ومعلومات الوقت
    const periods = day.timeline.periods || [];
    const workRange = day.timeline.work_range || { start: '08:00', end: '16:00' };

    if (periods.length > 0) {
        let barsHtml = '';
        
        periods.forEach((period) => {
            const barColor = period.is_active ? '#4cc1bb' : colorGradient;
            const tooltipText = `من ${period.start_time} إلى ${period.end_time}<br>المدة: ${period.duration}`;
            
            barsHtml += `
                <div style="position: absolute; top: 0; height: 100%; right: ${period.start_percent}%; width: ${period.width}%; background: ${barColor}; border-radius: 2px; cursor: pointer;" 
                     data-bs-toggle="tooltip" 
                     data-bs-placement="top" 
                     data-bs-title="${tooltipText}">
                </div>`;
        });

        // محور الوقت الديناميكي
        const startHour = parseInt(workRange.start.split(':')[0]);
        const endHour = parseInt(workRange.end.split(':')[0]);
        const hoursDiff = endHour - startHour;
        
        let timeMarks = [];
        for (let i = 0; i <= hoursDiff; i += 2) {
            const hour = startHour + i;
            if (hour <= endHour) {
                timeMarks.push(`<span>${hour.toString().padStart(2, '0')}:00</span>`);
            }
        }
        if (timeMarks.length < 4 || !timeMarks[timeMarks.length - 1].includes(workRange.end.split(':')[0])) {
            timeMarks.push(`<span>${workRange.end}</span>`);
        }

        timeAxisHtml = `
            <div class="d-flex justify-content-between mt-1" style="font-size: 0.625rem; color: #999;">
                ${timeMarks.join('')}
            </div>
        `;

        timelineHtml = `
            <div style="position: relative; width: 100%; height: 1.5rem; background: #f8f8f8; border-radius: 0.25rem; overflow: hidden;">
                ${barsHtml}
            </div>
            ${timeAxisHtml}
        `;
    } else {
        timelineHtml = `
            <div style="background: #fff9e6; color: #999; text-align: center; line-height: 1.5rem; border-radius: 0.25rem; font-size: 0.6875rem;">
                لا توجد فترات عمل
            </div>
        `;
    }

    const todayBadge = day.is_today ? '<span class="badge bg-label-primary" style="font-size: 0.625rem; padding: 2px 6px; margin-right: 6px;">اليوم</span>' : '';
    const activeBadge = day.is_active ? '<span class="badge bg-label-success" style="font-size: 0.625rem; padding: 2px 6px;">نشط</span>' : '';

    const statsHtml = `
        <div style="font-size: 0.6875rem; color: #666; text-align: center;">
            <div><strong>${day.work_hours}</strong></div>
            <div class="text-muted" style="font-size: 0.625rem;">${day.follows_count} متابعة</div>
        </div>
    `;

    return `
        <div style="padding: 0.5rem 1rem; border-bottom: 1px solid #f5f5f5;">
            <div class="row align-items-center g-2">
                <div class="col-auto" style="min-width: 120px;">
                    <div class="d-flex align-items-center">
                        <span style="font-size: 0.875rem; font-weight: 600; color: #372632;">${day.day_number}</span>
                        <span style="margin: 0 6px; color: #ddd;">-</span>
                        <span style="font-size: 0.75rem; color: #666;">${day.day_name}</span>
                    </div>
                    <div class="mt-1">
                        ${todayBadge}
                        ${activeBadge}
                    </div>
                </div>
                <div class="col">
                    ${timelineHtml}
                </div>
                <div class="col-auto" style="min-width: 80px;">
                    ${statsHtml}
                </div>
            </div>
        </div>
    `;
}

function getColorGradient(index, total) {
    const color1 = { r: 76, g: 193, b: 187 };
    const color2 = { r: 31, g: 42, b: 68 };
    const ratio = index / total;
    const r = Math.round(color1.r + (color2.r - color1.r) * ratio);
    const g = Math.round(color1.g + (color2.g - color1.g) * ratio);
    const b = Math.round(color1.b + (color2.b - color1.g) * ratio);
    return `rgb(${r}, ${g}, ${b})`;
}