<?php

namespace Diversworld\ContaoDiveclubBundle\Helper;

use Diversworld\ContaoDiveclubBundle\Model\DcCourseExercisesModel;
use Diversworld\ContaoDiveclubBundle\Model\DcCourseModulesModel;
use Diversworld\ContaoDiveclubBundle\Model\DcCourseStudentsModel;
use Diversworld\ContaoDiveclubBundle\Model\DcStudentExercisesModel;

class CourseProgressHelper
{
    public static function generateLabel(array $row)
    {
        $courseId = $row['id'];

        $modules = DcCourseModulesModel::findBy(['pid=?'], [$courseId]);
        if (!$modules) {
            return '<strong>' . $row['title'] . '</strong> (keine Module)';
        }

        $exerciseCount = 0;
        $completedCount = 0;

        foreach ($modules as $module) {
            $exercises = DcCourseExercisesModel::findBy(['pid=?'], [$module->id]);
            if (!$exercises) continue;

            foreach ($exercises as $exercise) {
                $exerciseCount++;

                $students = DcCourseStudentsModel::findBy(['course_id=?'], [$courseId]);
                if (!$students) continue;

                foreach ($students as $student) {
                    $status = DcStudentExercisesModel::findBy(['exercise_id=?', 'student_id=?'], [$exercise->id, $student->student_id]);

                    if ($status && $status->status === 'ok') {
                        $completedCount++;
                    }
                }
            }
        }

        if ($exerciseCount === 0) {
            return '<strong>' . $row['title'] . '</strong> (keine Übungen)';
        }

        $percent = round(($completedCount / $exerciseCount) * 100);

        $bar = sprintf(
            '<div style="width:150px; background:#ddd; border-radius:3px;">
                <div style="width:%s%%; background:#4caf50; height:8px; border-radius:3px;"></div>
            </div>',
            $percent
        );

        return sprintf(
            '<strong>%s</strong><br>%s%% abgeschlossen<br>%s',
            $row['title'],
            $percent,
            $bar
        );
    }
}
