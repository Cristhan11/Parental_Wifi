<?php

namespace Tests\Unit;

use App\Models\BlockedWebsite;
use App\Services\AccessAttemptAlertGrouping;
use Tests\TestCase;

class AccessAttemptAlertGroupingTest extends TestCase
{
    public function test_subject_site_label_uses_registrable_prefix(): void
    {
        $this->assertSame('facebook', AccessAttemptAlertGrouping::subjectSiteLabel('facebook.com'));
        $this->assertSame('youtube', AccessAttemptAlertGrouping::subjectSiteLabel('youtube.com'));
    }

    public function test_group_domain_for_blocked_domain_rule_is_primary_domain(): void
    {
        $rule = new BlockedWebsite([
            'domain' => 'Facebook.COM',
            'block_type' => 'domain',
            'block_subdomains' => true,
            'related_domains' => null,
        ]);

        $this->assertSame(
            'facebook.com',
            AccessAttemptAlertGrouping::groupDomainForBlockedRule($rule, 'graph.facebook.com')
        );
    }

    public function test_detail_host_prefers_url_host(): void
    {
        $this->assertSame(
            'edge-mqtt.facebook.com',
            AccessAttemptAlertGrouping::detailHostFromEvent('https://edge-mqtt.facebook.com/x', 'facebook.com')
        );
    }
}
