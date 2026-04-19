<?php

namespace support\adapter;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use Symfony\Component\DomCrawler\Crawler;

class ChaoxingClient
{
    private const LOGIN_KEY = 'u2oh6Vu^HWe4_AES';

    private const UA = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_6 like Mac OS X) '
        . 'AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 '
        . '(schild:bd8d074ebae1daa7f16e31ae5781def3) (device:iPhone14,5) '
        . 'Language/zh-Hans com.ssreader.ChaoXingStudy/ChaoXingStudy_3_6.2.9_ios_phone_202406131530_236 '
        . '(@Kalimdor)_8037712131006555090';

    private Client $http;
    private CookieJar $cookies;

    public function __construct()
    {
        $this->cookies = new CookieJar();
        $this->http = new Client([
            'cookies' => $this->cookies,
            'verify' => true,
            'timeout' => 10,
            'headers' => ['User-Agent' => self::UA],
        ]);
    }

    public function login(string $account, string $password): array
    {
        try {
            $resp = $this->http->post('https://passport2.chaoxing.com/fanyalogin', [
                'form_params' => [
                    'fid' => -1,
                    'uname' => $this->encrypt($account),
                    'password' => $this->encrypt($password),
                    't' => 'true',
                    'forbidotherlogin' => 0,
                    'validate' => '',
                ],
            ]);
            $result = json_decode((string) $resp->getBody(), true);
        } catch (\Throwable $e) {
            error_log("[ChaoxingClient] login request failed: " . $e->getMessage());
            return ['success' => false, 'userName' => '', 'msg' => '登录请求失败'];
        }

        if (empty($result['status'])) {
            return [
                'success' => false,
                'userName' => '',
                'msg' => $result['msg'] ?? '账号或密码错误',
            ];
        }

        $userName = $account;
        $schoolName = '';
        try {
            $info = json_decode(
                (string) $this->http->get('https://sso.chaoxing.com/apis/login/userLogin4Uname.do')->getBody(),
                true
            );
            $name = $info['msg']['name'] ?? '';
            if ($name !== '') {
                $userName = $name;
            }
            $schoolName = $info['msg']['schoolname'] ?? '';
        } catch (\Throwable $e) {
            error_log("[ChaoxingClient] getlogin_info failed: " . $e->getMessage());
        }

        return ['success' => true, 'userName' => $userName, 'schoolName' => $schoolName, 'msg' => ''];
    }

    public function queryCourses(): array
    {
        $mooc2 = $this->queryMooc2Courses();
        $mooc1 = $this->queryMooc1Courses();
        return array_merge($mooc2, $mooc1);
    }

    private function encrypt(string $value): string
    {
        $key = self::LOGIN_KEY;
        $padded = $this->pkcs7Pad($value, 16);
        $encrypted = openssl_encrypt($padded, 'aes-128-cbc', $key, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $key);
        return base64_encode($encrypted);
    }

    private function pkcs7Pad(string $data, int $blockSize): string
    {
        $padLen = $blockSize - (strlen($data) % $blockSize);
        return $data . str_repeat(chr($padLen), $padLen);
    }

    private function queryMooc2Courses(): array
    {
        try {
            $resp = $this->http->get('https://mooc2-ans.chaoxing.com/visit/courses/list', [
                'headers' => ['Host' => 'mooc2-ans.chaoxing.com'],
            ]);
            if ($resp->getStatusCode() !== 200) {
                return [];
            }
            $html = (string) $resp->getBody();
        } catch (\Throwable $e) {
            error_log("[ChaoxingClient] mooc2 courses request failed: " . $e->getMessage());
            return [];
        }

        $crawler = new Crawler($html);
        $infoList = [];
        $raw = [];

        $crawler->filter('li.course')->each(function (Crawler $node) use (&$infoList, &$raw) {
            $name = $node->filter('span.course-name')->count()
                ? trim($node->filter('span.course-name')->text(''))
                : '';

            $teacher = $node->filter('p.line2.color3')->count()
                ? trim($node->filter('p.line2.color3')->text(''))
                : '';

            $info = $node->attr('info') ?? '';
            if ($info !== '') {
                $infoList[] = $info;
            }

            $courseId = '';
            $linkNodes = $node->filter('a[href]');
            if ($linkNodes->count()) {
                $href = $linkNodes->first()->attr('href') ?? '';
                if (preg_match('/courseid=(\d+)/i', $href, $m)) {
                    $courseId = $m[1];
                }
            }

            $startTime = '不限时';
            $endTime = '不限时';
            $dateNode = $node->filter('p.margint10.line2.color2');
            if ($dateNode->count()) {
                $dateText = trim($dateNode->text(''));
                if (preg_match('/(\d{4}-\d{2}-\d{2})~(\d{4}-\d{2}-\d{2})/', $dateText, $dm)) {
                    $startTime = $dm[1];
                    $endTime = $dm[2];
                }
            }

            $raw[] = compact('courseId', 'name', 'teacher', 'info', 'startTime', 'endTime');
        });

        $progress = [];
        if (!empty($infoList)) {
            $progress = $this->getProgress($infoList);
        }

        $courses = [];
        foreach ($raw as $r) {
            $ti = $r['teacher'] ?: '暂时未查到';

            if ($r['info'] !== '' && !empty($progress)) {
                $clazzId = explode('_', $r['info'])[0] ?? '';
                if (isset($progress[$clazzId])) {
                    $p = $progress[$clazzId];
                    if ($p['done'] === 0 && $p['total'] === 0) {
                        $ti .= ' - 当前课程不需要观看';
                    } else {
                        $ti .= " - 任务点 {$p['done']}/{$p['total']} - 完成率 {$p['rate']}%";
                    }
                }
            }

            if ($r['startTime'] !== '不限时') {
                $ti .= " - 开课时间: {$r['startTime']} - 结课时间: {$r['endTime']}";
            }

            $courses[] = $this->makeCourse($r['courseId'], $r['name'], $ti, $r['startTime'], $r['endTime']);
        }

        return $courses;
    }

    private function getProgress(array $infoList): array
    {
        $headers = [
            'Accept' => 'application/json, text/javascript, */*; q=0.01',
            'Referer' => 'https://mooc2-ans.chaoxing.com/mooc2-ans/visit/interaction?moocDomain=https://mooc1-1.chaoxing.com/mooc-ans',
            'X-Requested-With' => 'XMLHttpRequest',
        ];

        try {
            $this->http->get('https://mooc2-ans.chaoxing.com/mooc2-ans/visit/version-status', [
                'headers' => $headers,
            ]);

            $this->http->post('https://mooc2-ans.chaoxing.com/mooc2-ans/visit/courselistdata', [
                'headers' => array_merge($headers, [
                    'Accept' => 'text/html, */*; q=0.01',
                    'Content-Type' => 'application/x-www-form-urlencoded; charset=UTF-8',
                ]),
                'form_params' => [
                    'courseType' => '1',
                    'courseFolderId' => '0',
                    'query' => '',
                    'pageHeader' => '-1',
                    'single' => '0',
                    'superstarClass' => '0',
                ],
            ]);

            $resp = $this->http->get('https://mooc2-ans.chaoxing.com/mooc2-ans/mycourse/stu-job-info', [
                'headers' => $headers,
                'query' => ['clazzPersonStr' => implode(',', $infoList)],
            ]);

            if ($resp->getStatusCode() !== 200) {
                return [];
            }

            $data = json_decode((string) $resp->getBody(), true);
        } catch (\Throwable $e) {
            error_log("[ChaoxingClient] getProgress failed: " . $e->getMessage());
            return [];
        }

        $result = [];
        if (!empty($data['status'])) {
            foreach ($data['jobArray'] ?? [] as $item) {
                $cid = (string) ($item['clazzId'] ?? '');
                $result[$cid] = [
                    'rate' => $item['jobRate'] ?? '',
                    'done' => (int) ($item['jobFinishCount'] ?? 0),
                    'total' => (int) ($item['jobCount'] ?? 0),
                ];
            }
        }

        return $result;
    }

    private function queryMooc1Courses(): array
    {
        try {
            $resp = $this->http->post('https://mooc1-1.chaoxing.com/mooc-ans/visit/courselistdata', [
                'headers' => [
                    'Accept' => 'text/html, */*; q=0.01',
                    'Content-Type' => 'application/x-www-form-urlencoded; charset=UTF-8',
                    'Origin' => 'https://mooc1-1.chaoxing.com',
                    'X-Requested-With' => 'XMLHttpRequest',
                ],
                'form_params' => [
                    'courseType' => '1',
                    'courseFolderId' => '0',
                    'baseEducation' => '0',
                    'superstarClass' => '',
                    'courseFolderSize' => '0',
                ],
            ]);
            $html = (string) $resp->getBody();
        } catch (\Throwable $e) {
            error_log("[ChaoxingClient] mooc1 courses request failed: " . $e->getMessage());
            return [];
        }

        $crawler = new Crawler($html);
        $courses = [];

        $crawler->filter('li.course.clearfix')->each(function (Crawler $node) use (&$courses) {
            $nameNode = $node->filter('span.course-name');
            $name = $nameNode->count() ? ($nameNode->attr('title') ?? '') : '';

            $teacherNode = $node->filter('p.line2');
            $teacher = $teacherNode->count() ? ($teacherNode->attr('title') ?? '') : '';
            if ($teacher === '') {
                $teacher = '暂时未查到';
            }

            $stateNode = $node->filter('a.not-open-tip');
            if ($stateNode->count()) {
                $state = trim($stateNode->text(''));
                if ($state !== '') {
                    $name = "【{$state}】{$name}";
                }
            }

            $courseId = '';
            $linkNode = $node->filter('a.color1');
            if ($linkNode->count()) {
                $href = $linkNode->attr('href') ?? '';
                if (preg_match('/courseId=(\d+)/', $href, $m)) {
                    $courseId = $m[1];
                }
            }

            $courses[] = $this->makeCourse($courseId, $name, $teacher);
        });

        return $courses;
    }

    private function makeCourse(
        string $courseId,
        string $name,
        string $teacherInfo,
        string $startTime = '不限时',
        string $endTime = '不限时'
    ): array {
        return [
            'courseId' => $courseId,
            'courseName' => $name,
            'teacherName' => $teacherInfo ?: '暂时未查到',
            'startTime' => $startTime ?: '不限时',
            'endTime' => $endTime ?: '不限时',
        ];
    }
}
