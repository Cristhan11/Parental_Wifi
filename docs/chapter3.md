# CHAPTER 3: DESIGN TRADE-OFFS

This chapter analyzes the critical design trade-offs encountered during the development of the Child-Centric WiFi Monitoring and Control System with Learning Access Management and Automated Reporting. Engineering design decisions rarely involve perfect solutions; instead, they require balancing competing priorities and constraints. This analysis examines how various trade-offs were evaluated and resolved, providing insight into the rationale behind key architectural and implementation choices that shaped the final system design.

## 3.1 Summary of Constraints

The development of this parental control system was influenced by multiple categories of constraints that directly impacted design decisions. These constraints, as identified in Chapter 1.6, can be categorized into technical, software, operational, security, and resource limitations.

### Technical Constraints

The system operates within several technical boundaries that limit certain capabilities. Hardware compatibility requirements restrict the system to PLDT or Globe modem models that support captive portal functionality or DNS redirection, potentially limiting deployment in households with incompatible router configurations. Network infrastructure limitations mean that system performance depends on local Wi-Fi network stability, with internet speed and signal strength variations affecting both portal responsiveness and dashboard accessibility. Browser dependency introduces compatibility challenges across older browsers or devices with outdated software. Perhaps most significantly, HTTPS and encryption restrictions prevent the system from inspecting actual content of encrypted traffic, limiting content filtering to domain-level control rather than granular page-level analysis.

### Software Constraints

Software limitations stem primarily from resource and technology choices. The web-based-only design excludes native mobile application development, preventing implementation of application-level blocking features available in native solutions. Limited backend processing power on the Raspberry Pi 4B makes computationally intensive processes like deep packet inspection infeasible, restricting the depth of traffic analysis possible. The requirement to use only open-source and free tools due to budget limitations excludes certain advanced features available in commercial solutions.

### Operational Constraints

Operational considerations shape both system design and user experience requirements. User knowledge and technical skills limitations necessitate a simple, intuitive interface that may restrict the addition of complex features that could confuse non-technical parents. Maintenance and monitoring requirements mean parents must be willing to perform basic upkeep tasks or have access to technical support. Testing environment limitations restrict evaluation to home network setups with small user samples, which may not fully represent all possible usage scenarios.

### Security and Privacy Constraints

Security and privacy considerations impose important limitations on system capabilities. Data privacy regulations require limiting the amount of user information stored and protecting sensitive data, though full encryption may be challenging given limited hosting resources. The system cannot perform deep content analysis for privacy and security reasons, monitoring only domain-level access through logs rather than actual messages, videos, or detailed content viewed by children.

### Resource Constraints

Resource limitations directly impact feature scope and technology selection. Limited budget restricts the system to low-cost or freely available tools, affecting technology and service selection. Time constraints limit the development of additional features such as AI-based content filtering or advanced analytics capabilities. The small development team limits the complexity and scale of features that can be implemented within the project timeline.

These constraints collectively influenced every major design decision, from hardware selection to software architecture choices. The following sections analyze how these constraints were balanced through specific trade-offs in economic, safety, risk, environmental, and sustainability considerations.

## 3.2 Trade-Offs

Design trade-offs represent deliberate choices between competing alternatives, each with distinct advantages and disadvantages. This section examines five critical trade-off categories that significantly influenced the system's final design: economic considerations, safety and cybersecurity, risk management, environmental impact, and sustainability.

### 3.2.1 Tradeoff 1: Economic (Material/Equipment Cost)

The economic trade-off analysis evaluates the cost implications of hardware and software choices, balancing initial investment against long-term operational expenses and system capabilities.

#### Alternative Approaches Considered

Several hardware and software alternatives were evaluated based on economic considerations:

**Alternative A: Commercial Router with Custom Firmware**
- **Cost**: $150-$300 for compatible router, plus potential warranty voiding
- **Advantages**: Native routing performance, minimal additional hardware
- **Disadvantages**: High risk of bricking routers, limited compatibility with ISP-issued PLDT/Globe units, potential need for replacement if firmware installation fails

**Alternative B: Cloud-Managed Parental Control Service**
- **Cost**: $10-$30 per month subscription fees, plus potential cloud hosting costs
- **Advantages**: No hardware investment, automatic updates, enterprise-grade analytics
- **Disadvantages**: Ongoing monthly expenses, constant internet dependency, privacy concerns with external data storage

**Alternative C: Dedicated Server or Mini PC**
- **Cost**: $300-$800 for mini PC or low-end server hardware
- **Advantages**: Higher processing power, more storage capacity, better performance
- **Disadvantages**: Significantly higher initial cost, higher power consumption, overkill for home use

**Alternative D: Raspberry Pi 4B with SSD (Selected)**
- **Cost**: Approximately $100-$150 total (Raspberry Pi 4B: $55, Kingston A400 480GB SSD: $40, Power supply and accessories: $15-$55)
- **Advantages**: Low initial investment, sufficient processing power for requirements, energy efficient, open-source software stack (no licensing fees)
- **Disadvantages**: Limited processing power compared to dedicated servers, requires careful resource management

#### Selected Solution: Economic Analysis

The chosen solution utilizes a Raspberry Pi 4B with a 480GB SSD, representing a cost-effective approach that balances functionality with affordability. The total hardware investment of approximately $100-$150 provides a complete system capable of serving as an access point, web server, firewall, and monitoring device simultaneously.

**Cost Breakdown:**
- Raspberry Pi 4B (4GB RAM): $55
- Kingston A400 480GB SSD: $40
- 5V/3A USB-C Power Supply: $10
- Heat Sinks and Cooling: $5
- Ethernet Cable and Accessories: $5-$10
- **Total Hardware Cost: $115-$120**

**Software Costs:**
- Operating System: Raspberry Pi OS Lite (Free, open-source)
- Web Framework: Laravel 12 (Free, open-source)
- Database: MariaDB (Free, open-source)
- Web Server: Nginx (Free, open-source)
- Captive Portal: NoDogSplash (Free, open-source)
- **Total Software Cost: $0**

**Total System Cost: $115-$120 (one-time)**

#### Economic Trade-Off Evaluation

The economic trade-off prioritizes low initial investment and zero ongoing software licensing fees over higher processing power and commercial support. This decision aligns with the project's budget constraints and makes the system accessible to families with limited financial resources.

**Advantages of Selected Approach:**
1. **Low Initial Investment**: At $115-$120, the system costs less than most commercial parental control solutions' annual subscription fees ($120-$360 per year)
2. **No Recurring Costs**: Unlike cloud-based services requiring monthly subscriptions, this system has zero ongoing software costs
3. **Open-Source Software Stack**: All software components are free and open-source, eliminating licensing fees
4. **Cost-Effective for Multiple Children**: The one-time hardware investment serves multiple child devices simultaneously, unlike per-device subscription models
5. **Long-Term Cost Savings**: Over a 3-year period, the system costs approximately $115-$120 total, compared to $360-$1,080 for cloud-based subscriptions

**Disadvantages and Mitigations:**
1. **Limited Processing Power**: The Raspberry Pi's ARM processor limits computational capabilities compared to x86-based systems
   - **Mitigation**: Optimized software architecture, lightweight background jobs, efficient database queries, and pre-encoded video content
2. **No Commercial Support**: Open-source software lacks commercial technical support
   - **Mitigation**: Comprehensive documentation, community support resources, and system designed for maintainability
3. **Potential Hardware Replacement**: Raspberry Pi hardware may need replacement after several years of continuous operation
   - **Mitigation**: Estimated 5-7 year lifespan with proper cooling and power supply, still more cost-effective than annual subscriptions

**Cost-Benefit Analysis:**

The economic trade-off demonstrates clear financial advantages. Over a 5-year period:
- **Selected Solution**: $115-$120 (one-time) + potential replacement hardware ($115) = $230-$235 total
- **Cloud Subscription Alternative**: $10/month × 60 months = $600 minimum, or $30/month × 60 months = $1,800
- **Cost Savings**: $365-$1,570 over 5 years

This economic analysis justifies the selection of Raspberry Pi 4B as the hardware platform, providing a cost-effective solution that maintains system functionality while remaining accessible to families with limited budgets.

### 3.2.2 Tradeoff 2: Safety (Cybersecurity Risk Score)

The safety trade-off analysis evaluates cybersecurity risks associated with different architectural approaches, balancing security measures against system functionality and usability.

#### Alternative Approaches Considered

Several security architectures were evaluated based on cybersecurity risk assessment:

**Alternative A: Cloud-Based Data Storage and Processing**
- **Cybersecurity Risk Score**: High (7/10)
- **Advantages**: Enterprise-grade security infrastructure, automatic security updates, professional security monitoring
- **Disadvantages**: Data transmitted to external servers (privacy risk), dependency on internet connectivity for security updates, potential data breaches at cloud provider, children's browsing data stored on external servers

**Alternative B: Local Storage with Remote Dashboard Access via Port Forwarding**
- **Cybersecurity Risk Score**: Medium-High (6/10)
- **Advantages**: Data remains local, direct internet access to dashboard
- **Disadvantages**: Exposes system to public internet, requires firewall configuration, vulnerable to port scanning attacks, potential unauthorized access if misconfigured

**Alternative C: Fully Offline System (No Remote Access)**
- **Cybersecurity Risk Score**: Low (2/10)
- **Advantages**: Minimal attack surface, no internet exposure, maximum data privacy
- **Disadvantages**: Parents cannot monitor remotely, limited functionality when away from home, reduced convenience

**Alternative D: Local Storage with Secure Remote Access (Selected)**
- **Cybersecurity Risk Score**: Medium (4/10)
- **Advantages**: Data remains local, secure remote access options (VPN, cloud tunneling), flexible security configuration
- **Disadvantages**: Requires proper security configuration, potential misconfiguration risks, depends on user security practices

#### Selected Solution: Cybersecurity Risk Analysis

The chosen solution implements a multi-layered security architecture that prioritizes local data storage while providing optional secure remote access. This approach balances security with functionality, achieving a moderate cybersecurity risk score of 4/10.

**Security Architecture Components:**

1. **Local Data Storage**
   - All browsing logs, device information, and user data stored locally on Raspberry Pi
   - No transmission of children's browsing data to external servers
   - Data privacy maintained within home network
   - **Risk Reduction**: Eliminates cloud data breach risks

2. **Network-Level Security**
   - Firewall rules (iptables/nftables) control network traffic at MAC address level
   - ScriptExecutor service implements whitelist validation, path validation, and argument sanitization
   - Sudoers configuration restricts script execution to approved scripts only
   - **Risk Reduction**: Prevents unauthorized command execution and network access

3. **Application-Level Security**
   - OWASP Top Ten security guidelines implementation
   - CSRF protection on all form submissions
   - Secure session management
   - Password hashing using bcrypt
   - Input validation and sanitization
   - **Risk Reduction**: Protects against common web application vulnerabilities

4. **Secure Remote Access Options**
   - VPN connections (recommended): Encrypted tunnel, requires VPN server setup
   - Cloud tunneling services (e.g., ngrok, Cloudflare Tunnel): Secure proxy, no port forwarding required
   - Port forwarding with HTTPS: Requires proper SSL certificate configuration
   - **Risk Reduction**: Multiple secure access methods available, user chooses based on security requirements

**Cybersecurity Risk Score Breakdown:**

| Risk Category | Risk Level | Score | Mitigation |
|--------------|------------|-------|------------|
| Data Privacy | Low | 1/10 | Local storage, no cloud transmission |
| Network Security | Medium | 4/10 | Firewall rules, MAC address filtering, ScriptExecutor security |
| Application Security | Medium | 4/10 | OWASP guidelines, CSRF protection, input validation |
| Remote Access Security | Medium-High | 5/10 | Optional secure methods (VPN recommended), user configuration dependent |
| Physical Security | Low | 2/10 | Local device, physical access control by parents |
| **Overall Risk Score** | **Medium** | **4/10** | Multi-layered security architecture |

#### Safety Trade-Off Evaluation

The safety trade-off prioritizes local data storage and privacy over cloud-based convenience, achieving a moderate cybersecurity risk score while maintaining system functionality.

**Advantages of Selected Approach:**
1. **Data Privacy**: Children's browsing data never leaves the home network, eliminating cloud data breach risks
2. **Local Control**: Parents maintain complete control over security configuration and data storage
3. **Flexible Security**: Multiple secure remote access options allow parents to choose security level based on needs
4. **No External Dependencies**: System security does not depend on third-party cloud provider security practices
5. **Compliance**: Local storage aligns with data privacy principles (GDPR-inspired practices) without requiring external compliance

**Disadvantages and Mitigations:**
1. **User Configuration Dependency**: Security depends on proper configuration by parents
   - **Mitigation**: Comprehensive security documentation, default secure configurations, security best practices guide
2. **Remote Access Complexity**: Secure remote access requires technical knowledge (VPN setup, cloud tunneling)
   - **Mitigation**: Step-by-step setup guides, recommended secure methods (VPN), optional feature (system works without remote access)
3. **Limited Enterprise Security Features**: Lacks enterprise-grade security monitoring and automatic threat detection
   - **Mitigation**: Log monitoring capabilities, security event logging, regular security update procedures

**Risk Comparison:**

Compared to cloud-based alternatives, the selected approach significantly reduces data privacy risks (1/10 vs. 7/10) while maintaining moderate overall security (4/10 vs. 7/10). The trade-off accepts slightly higher configuration complexity in exchange for complete data privacy and local control.

**Security Best Practices Implementation:**

The system implements multiple security layers following OWASP guidelines:
- **Injection Attack Prevention**: Laravel's query parameterization, input sanitization
- **Authentication Security**: Secure password hashing, session management
- **CSRF Protection**: Token-based protection on all forms
- **Secure Script Execution**: Whitelist validation, path validation, argument sanitization
- **Network Security**: MAC address-based firewall rules, iptables configuration

This multi-layered approach achieves a balanced cybersecurity risk score of 4/10, providing adequate security for home network use while maintaining data privacy and local control.

### 3.2.3 Tradeoff 3: Risk (Failure Rate)

The risk trade-off analysis evaluates system reliability and failure rates associated with different hardware and software choices, balancing reliability against cost and complexity.

#### Alternative Approaches Considered

Several reliability and failure risk scenarios were evaluated:

**Alternative A: Commercial Router with Custom Firmware**
- **Failure Risk**: High (8/10)
- **Failure Modes**: Router bricking during firmware installation, hardware incompatibility, firmware update failures, loss of warranty
- **Advantages**: Native hardware reliability if successful
- **Disadvantages**: High risk of permanent hardware failure, potential need for router replacement

**Alternative B: Cloud-Based Service with Local Gateway**
- **Failure Risk**: Medium (5/10)
- **Failure Modes**: Internet connectivity dependency, cloud service outages, local gateway hardware failure, service discontinuation
- **Advantages**: Redundant cloud infrastructure, automatic failover
- **Disadvantages**: Complete system failure if internet is down, dependency on external service availability

**Alternative C: Redundant Hardware Setup (Primary + Backup)**
- **Failure Risk**: Low (2/10)
- **Failure Modes**: Both systems failing simultaneously (very low probability)
- **Advantages**: High reliability, automatic failover capability
- **Disadvantages**: Double hardware cost ($230-$240), increased complexity, overkill for home use

**Alternative D: Single Raspberry Pi 4B with SSD (Selected)**
- **Failure Risk**: Medium (5/10)
- **Failure Modes**: Hardware failure (Pi or SSD), power supply failure, software crashes, network service failures
- **Advantages**: Cost-effective, sufficient reliability for home use, replaceable components
- **Disadvantages**: Single point of failure, no automatic redundancy

#### Selected Solution: Failure Rate Analysis

The chosen solution utilizes a single Raspberry Pi 4B with SSD storage, representing a balanced approach that provides adequate reliability for home use while maintaining cost-effectiveness. The system achieves a medium failure risk score of 5/10.

**Failure Mode Analysis:**

1. **Hardware Failure Risks**
   - **Raspberry Pi 4B Failure**: Estimated failure rate: 2-5% over 5 years with proper cooling
     - **Mitigation**: Proper cooling (heat sinks), stable power supply (5V/3A with surge protection), estimated 5-7 year lifespan
   - **SSD Failure**: Estimated failure rate: 1-3% over 5 years (SSD more reliable than microSD)
     - **Mitigation**: SSD chosen over microSD for better wear-leveling and durability, suitable for continuous write operations
   - **Power Supply Failure**: Estimated failure rate: 5-10% over 5 years
     - **Mitigation**: Quality power supply with surge protection, easily replaceable component
   - **Overall Hardware Failure Risk**: Medium (4/10)

2. **Software Failure Risks**
   - **Operating System Crashes**: Low risk with Raspberry Pi OS Lite (stable, well-tested)
     - **Mitigation**: Automatic service restart scripts, systemd service management, log monitoring
   - **Application Crashes**: Medium risk (Laravel application, background jobs)
     - **Mitigation**: Comprehensive error handling, queue worker monitoring, automatic job retry mechanisms, log monitoring
   - **Database Corruption**: Low-Medium risk (MariaDB on SSD)
     - **Mitigation**: Regular database backups, transaction logging, SSD reliability
   - **Network Service Failures**: Medium risk (hostapd, dnsmasq, NoDogSplash)
     - **Mitigation**: Service monitoring, automatic restart scripts, health check mechanisms
   - **Overall Software Failure Risk**: Medium (5/10)

3. **Network Failure Risks**
   - **Internet Connectivity Loss**: High probability (depends on ISP)
     - **Impact**: Remote dashboard access unavailable, but local system continues functioning
     - **Mitigation**: System designed for offline operation, local dashboard remains accessible
   - **WiFi Access Point Failure**: Low-Medium risk
     - **Mitigation**: Standard WiFi protocols (IEEE 802.11), reliable hostapd service, automatic reconnection handling
   - **Overall Network Failure Risk**: Medium (5/10)

**Failure Rate Score Breakdown:**

| Failure Category | Failure Risk | Score | Mitigation Effectiveness |
|------------------|--------------|-------|-------------------------|
| Hardware Failure | Medium | 4/10 | Proper cooling, quality components, estimated 5-7 year lifespan |
| Software Crashes | Medium | 5/10 | Error handling, service monitoring, automatic restarts |
| Database Corruption | Low-Medium | 4/10 | Regular backups, transaction logging, SSD reliability |
| Network Service Failures | Medium | 5/10 | Service monitoring, automatic restarts, health checks |
| Power Supply Failure | Medium | 5/10 | Quality power supply, easily replaceable |
| **Overall Failure Risk** | **Medium** | **5/10** | Multiple mitigation strategies implemented |

#### Risk Trade-Off Evaluation

The risk trade-off accepts a medium failure risk (5/10) in exchange for cost-effectiveness and simplicity, implementing multiple mitigation strategies to minimize failure impact.

**Advantages of Selected Approach:**
1. **Cost-Effective Reliability**: Achieves adequate reliability for home use at low cost
2. **Replaceable Components**: Individual components (Pi, SSD, power supply) can be replaced independently
3. **Offline Operation**: System continues functioning during internet outages
4. **Service Monitoring**: Background jobs and services can be monitored and automatically restarted
5. **Data Preservation**: Database backups and transaction logging protect against data loss

**Disadvantages and Mitigations:**
1. **Single Point of Failure**: No hardware redundancy
   - **Mitigation**: Estimated 5-7 year hardware lifespan, easily replaceable components, cost-effective replacement ($115-$120)
2. **Manual Recovery Required**: Some failures may require manual intervention
   - **Mitigation**: Comprehensive documentation, automated service restarts, health check scripts
3. **No Automatic Failover**: System cannot automatically switch to backup hardware
   - **Mitigation**: Acceptable for home use, manual recovery procedures documented, hardware replacement is cost-effective

**Reliability Comparison:**

Compared to alternatives:
- **Cloud-Based Service**: Similar failure risk (5/10) but different failure modes (internet dependency vs. hardware failure)
- **Redundant Hardware**: Lower failure risk (2/10) but double the cost ($230-$240 vs. $115-$120)
- **Commercial Router Customization**: Higher failure risk (8/10) due to firmware installation risks

**Mitigation Strategies Implemented:**

1. **Hardware Reliability**
   - Quality components (Raspberry Pi 4B, Kingston SSD)
   - Proper cooling (heat sinks)
   - Stable power supply (5V/3A with surge protection)
   - Estimated 5-7 year lifespan

2. **Software Reliability**
   - Comprehensive error handling in Laravel application
   - Background job monitoring and automatic retry
   - Service health checks and automatic restarts
   - Database transaction logging and backups

3. **Recovery Procedures**
   - System documentation for troubleshooting
   - Service restart scripts
   - Database backup and restore procedures
   - Hardware replacement guides

The selected approach achieves a balanced failure risk score of 5/10, providing adequate reliability for home parental control use while maintaining cost-effectiveness and simplicity. The multiple mitigation strategies minimize failure impact and provide recovery procedures for common failure scenarios.

### 3.2.4 Tradeoff 4: Environmental (Cost-Benefit Analysis to the Environment)

The environmental trade-off analysis evaluates the environmental impact of different hardware and operational approaches, considering energy consumption, electronic waste, and carbon footprint.

#### Alternative Approaches Considered

Several environmental impact scenarios were evaluated:

**Alternative A: Cloud-Based Service with Local Gateway**
- **Environmental Impact**: High (7/10 negative impact)
- **Factors**: Cloud data center energy consumption, network infrastructure energy use, local gateway device power consumption, data transmission energy costs
- **Advantages**: Shared cloud infrastructure (efficiency at scale)
- **Disadvantages**: High energy consumption at data centers, network transmission energy, multiple devices (local + cloud)

**Alternative B: Dedicated Server or Mini PC**
- **Environmental Impact**: Medium-High (6/10 negative impact)
- **Factors**: Higher power consumption (30-100W), larger carbon footprint, more electronic waste at end of life
- **Advantages**: Longer lifespan, better performance
- **Disadvantages**: Higher energy consumption, larger physical footprint, more materials in manufacturing

**Alternative C: Commercial Router with Custom Firmware**
- **Environmental Impact**: Low-Medium (3/10 negative impact)
- **Factors**: Existing router reuse, minimal additional hardware, low power consumption
- **Advantages**: Reuses existing hardware, minimal waste
- **Disadvantages**: High failure risk may lead to premature replacement, router disposal

**Alternative D: Raspberry Pi 4B with SSD (Selected)**
- **Environmental Impact**: Low (2/10 negative impact)
- **Factors**: Low power consumption (3-5W idle, 5-7W under load), small physical footprint, long lifespan, minimal electronic waste
- **Advantages**: Energy efficient, compact design, durable components
- **Disadvantages**: Still consumes electricity, produces electronic waste at end of life

#### Selected Solution: Environmental Impact Analysis

The chosen solution utilizes a Raspberry Pi 4B with SSD, representing an environmentally conscious approach with low environmental impact. The system achieves a low environmental impact score of 2/10.

**Environmental Impact Factors:**

1. **Energy Consumption**
   - **Power Usage**: 3-5W idle, 5-7W under typical load, 7-10W peak (video streaming)
   - **Annual Energy Consumption**: 
     - Idle (20 hours/day): 3W × 20h × 365 days = 21.9 kWh/year
     - Active (4 hours/day): 7W × 4h × 365 days = 10.2 kWh/year
     - **Total: ~32 kWh/year** (assuming 24/7 operation)
   - **Carbon Footprint**: 
     - Philippines average grid emission: ~0.5 kg CO2/kWh
     - Annual CO2 emissions: 32 kWh × 0.5 kg CO2/kWh = **16 kg CO2/year**
   - **Comparison**:
     - Mini PC (50W average): ~438 kWh/year, ~219 kg CO2/year
     - Cloud service (estimated): ~100-200 kWh/year (including data center and transmission), ~50-100 kg CO2/year
     - **Energy Savings**: 87-93% compared to mini PC, 68-84% compared to cloud service

2. **Electronic Waste**
   - **Hardware Components**: Raspberry Pi 4B (small form factor), SSD (standard component), minimal accessories
   - **Physical Footprint**: Very small (85mm × 56mm × 21mm for Pi, 2.5" SSD)
   - **Material Usage**: Minimal materials compared to full-size computers or servers
   - **End-of-Life**: Estimated 5-7 year lifespan, components can be recycled or repurposed
   - **Waste Reduction**: Significantly less electronic waste compared to dedicated servers or mini PCs

3. **Manufacturing Impact**
   - **Component Manufacturing**: Raspberry Pi manufactured efficiently at scale, SSD standard component
   - **Transportation**: Lightweight components reduce shipping energy
   - **Packaging**: Minimal packaging compared to full computer systems

4. **Operational Environmental Benefits**
   - **Local Processing**: No cloud data transmission reduces network energy consumption
   - **Offline Operation**: System functions without constant internet connectivity, reducing network infrastructure load
   - **Efficient Software**: Lightweight software stack (Raspberry Pi OS Lite, optimized Laravel) minimizes resource usage

**Environmental Impact Score Breakdown:**

| Environmental Factor | Impact Level | Score | Details |
|---------------------|--------------|-------|---------|
| Energy Consumption | Low | 2/10 | 32 kWh/year, 16 kg CO2/year (87-93% less than alternatives) |
| Electronic Waste | Low | 2/10 | Small form factor, 5-7 year lifespan, recyclable components |
| Manufacturing Impact | Low | 2/10 | Efficient manufacturing, lightweight components |
| Carbon Footprint | Low | 2/10 | Minimal CO2 emissions compared to alternatives |
| **Overall Environmental Impact** | **Low** | **2/10** | Significantly lower than cloud or dedicated server alternatives |

#### Environmental Trade-Off Evaluation

The environmental trade-off prioritizes low energy consumption and minimal environmental impact, achieving a low environmental impact score of 2/10 while maintaining system functionality.

**Advantages of Selected Approach:**
1. **Low Energy Consumption**: 32 kWh/year (87-93% less than mini PC alternatives)
2. **Minimal Carbon Footprint**: 16 kg CO2/year (significantly lower than cloud or server alternatives)
3. **Small Physical Footprint**: Compact design reduces material usage and waste
4. **Long Lifespan**: 5-7 year estimated lifespan reduces replacement frequency
5. **Local Processing**: Eliminates cloud data transmission energy costs
6. **Recyclable Components**: Standard components can be recycled or repurposed at end of life

**Disadvantages and Context:**
1. **Still Consumes Energy**: System requires continuous power (24/7 operation)
   - **Context**: Energy consumption is minimal (3-7W), significantly lower than alternatives
2. **Electronic Waste at End of Life**: Components will eventually become electronic waste
   - **Context**: Small form factor, recyclable materials, long lifespan minimizes waste impact
3. **Manufacturing Impact**: Components still require manufacturing resources
   - **Context**: Efficient manufacturing at scale, lightweight components minimize impact

**Environmental Cost-Benefit Analysis:**

**Environmental Costs:**
- Annual energy consumption: 32 kWh
- Annual CO2 emissions: 16 kg CO2
- Electronic waste: ~0.5 kg at end of life (after 5-7 years)

**Environmental Benefits:**
- Eliminates cloud data center energy consumption for this use case
- Reduces network transmission energy (local processing)
- Minimal electronic waste compared to alternatives
- Long lifespan reduces replacement frequency

**Comparison to Alternatives:**

| Alternative | Annual Energy | Annual CO2 | Environmental Score |
|-------------|---------------|------------|---------------------|
| Raspberry Pi 4B (Selected) | 32 kWh | 16 kg | 2/10 (Low) |
| Mini PC | 438 kWh | 219 kg | 6/10 (Medium-High) |
| Cloud Service | 100-200 kWh | 50-100 kg | 7/10 (High) |
| Commercial Router Reuse | 20-30 kWh | 10-15 kg | 3/10 (Low-Medium) |

**Net Environmental Impact:**

The selected approach provides significant environmental benefits compared to alternatives:
- **93% less energy** than mini PC alternative
- **68-84% less energy** than cloud service alternative
- **Minimal carbon footprint** (16 kg CO2/year)
- **Small electronic waste footprint** (compact, recyclable components)

The environmental trade-off demonstrates that the Raspberry Pi 4B solution achieves low environmental impact (2/10) while maintaining full system functionality, making it an environmentally responsible choice for home parental control systems.

### 3.2.5 Tradeoff 5: Sustainability (Power Consumption/Life Span)

The sustainability trade-off analysis evaluates long-term viability, considering power consumption efficiency, hardware lifespan, upgradeability, and maintenance requirements.

#### Alternative Approaches Considered

Several sustainability scenarios were evaluated:

**Alternative A: High-Performance Mini PC**
- **Sustainability Score**: Medium (5/10)
- **Power Consumption**: 30-100W (6-20x higher than Pi)
- **Lifespan**: 5-8 years
- **Advantages**: Longer lifespan, better performance, easier upgrades
- **Disadvantages**: High power consumption, larger carbon footprint, higher replacement cost

**Alternative B: Cloud-Based Service**
- **Sustainability Score**: Medium-Low (4/10)
- **Power Consumption**: Dependent on local gateway + cloud infrastructure
- **Lifespan**: Service-dependent (may discontinue)
- **Advantages**: No local hardware maintenance, automatic updates
- **Disadvantages**: Ongoing subscription costs, service dependency, cloud infrastructure energy consumption

**Alternative C: Commercial Router Customization**
- **Sustainability Score**: Medium (5/10)
- **Power Consumption**: 5-15W (similar to Pi)
- **Lifespan**: 3-5 years (router lifespan)
- **Advantages**: Reuses existing hardware, low power consumption
- **Disadvantages**: High failure risk, limited upgradeability, router replacement cycles

**Alternative D: Raspberry Pi 4B with SSD (Selected)**
- **Sustainability Score**: High (8/10)
- **Power Consumption**: 3-7W (very low)
- **Lifespan**: 5-7 years (estimated)
- **Advantages**: Extremely low power consumption, good lifespan, cost-effective replacement
- **Disadvantages**: Limited upgradeability, may need replacement after 5-7 years

#### Selected Solution: Sustainability Analysis

The chosen solution achieves a high sustainability score of 8/10, balancing low power consumption, adequate lifespan, and cost-effective operation over the system's lifetime.

**Sustainability Factors:**

1. **Power Consumption Efficiency**
   - **Idle Power**: 3-5W (extremely efficient)
   - **Typical Load**: 5-7W (web server + WiFi AP + database)
   - **Peak Load**: 7-10W (video streaming + multiple operations)
   - **Power Efficiency Rating**: Excellent (9/10)
   - **Comparison**:
     - Mini PC: 30-100W (4-14x higher)
     - Cloud gateway: 10-20W local + cloud infrastructure
     - Commercial router: 5-15W (similar range)
   - **Energy Efficiency**: 87-93% more efficient than mini PC alternatives

2. **Hardware Lifespan**
   - **Raspberry Pi 4B**: Estimated 5-7 years with proper cooling and power supply
     - Factors: ARM processor durability, proper thermal management, stable power supply
   - **SSD Storage**: Estimated 5-7 years (480GB Kingston A400, designed for continuous operation)
     - Factors: Wear-leveling technology, suitable for logging and database operations
   - **Power Supply**: Estimated 3-5 years (replaceable component)
   - **Overall System Lifespan**: 5-7 years (limited by Pi or SSD, whichever fails first)
   - **Lifespan Rating**: Good (7/10)

3. **Upgradeability and Maintenance**
   - **Hardware Upgrades**: Limited (Pi cannot be upgraded, but entire system can be replaced cost-effectively)
   - **Software Upgrades**: Excellent (open-source software, regular updates available)
   - **Component Replacement**: Individual components (SSD, power supply) can be replaced
   - **System Replacement Cost**: $115-$120 (cost-effective for 5-7 year lifespan)
   - **Maintenance Requirements**: Low (periodic software updates, log monitoring)
   - **Upgradeability Rating**: Medium (6/10)

4. **Long-Term Cost Sustainability**
   - **5-Year Total Cost of Ownership**:
     - Hardware: $115-$120 (initial) + $115-$120 (replacement at year 5-7) = $230-$240
     - Power: 32 kWh/year × 5 years × $0.15/kWh = $24
     - Software: $0 (open-source)
     - **Total 5-Year Cost: $254-$264**
   - **10-Year Total Cost of Ownership** (with one replacement):
     - Hardware: $115-$120 (initial) + $115-$120 (replacement) = $230-$240
     - Power: 32 kWh/year × 10 years × $0.15/kWh = $48
     - Software: $0
     - **Total 10-Year Cost: $278-$288**
   - **Cost Sustainability**: Excellent (9/10)

5. **Environmental Sustainability**
   - **Carbon Footprint**: 16 kg CO2/year (very low)
   - **Electronic Waste**: Minimal (small form factor, recyclable)
   - **Resource Efficiency**: High (low material usage, efficient manufacturing)
   - **Environmental Rating**: Excellent (9/10)

**Sustainability Score Breakdown:**

| Sustainability Factor | Score | Weight | Weighted Score |
|----------------------|-------|--------|----------------|
| Power Consumption Efficiency | 9/10 | 25% | 2.25 |
| Hardware Lifespan | 7/10 | 25% | 1.75 |
| Upgradeability/Maintenance | 6/10 | 15% | 0.90 |
| Cost Sustainability | 9/10 | 20% | 1.80 |
| Environmental Sustainability | 9/10 | 15% | 1.35 |
| **Overall Sustainability Score** | **8/10** | **100%** | **8.05/10** |

#### Sustainability Trade-Off Evaluation

The sustainability trade-off achieves a high sustainability score of 8/10, prioritizing long-term efficiency and viability while maintaining system functionality.

**Advantages of Selected Approach:**
1. **Exceptional Power Efficiency**: 3-7W power consumption (87-93% more efficient than alternatives)
2. **Adequate Lifespan**: 5-7 year estimated lifespan provides long-term value
3. **Cost-Effective Operation**: $254-$264 total cost over 5 years (including power and potential replacement)
4. **Low Environmental Impact**: Minimal carbon footprint and electronic waste
5. **Software Sustainability**: Open-source software ensures long-term availability and updates
6. **Maintenance Efficiency**: Low maintenance requirements, easy component replacement

**Disadvantages and Mitigations:**
1. **Limited Hardware Upgradeability**: Pi cannot be upgraded, requires full system replacement
   - **Mitigation**: Cost-effective replacement ($115-$120), 5-7 year lifespan minimizes replacement frequency
2. **Potential Replacement Needed**: System may need replacement after 5-7 years
   - **Mitigation**: Low replacement cost, long lifespan, cost-effective over 10-year period ($278-$288 total)
3. **Performance Limitations**: May become insufficient for future requirements
   - **Mitigation**: System designed for current requirements, future Raspberry Pi models may offer better performance if needed

**Sustainability Comparison:**

| Alternative | Power (W) | Lifespan | 5-Year Cost | Sustainability Score |
|-------------|-----------|----------|-------------|---------------------|
| Raspberry Pi 4B (Selected) | 3-7 | 5-7 years | $254-$264 | 8/10 (High) |
| Mini PC | 30-100 | 5-8 years | $600-$800 | 5/10 (Medium) |
| Cloud Service | 10-20 + cloud | Service-dependent | $600-$1,800 | 4/10 (Medium-Low) |
| Commercial Router | 5-15 | 3-5 years | $200-$400 | 5/10 (Medium) |

**Long-Term Sustainability Analysis:**

**10-Year Projection:**
- **Hardware Replacements**: 1-2 replacements (at years 5-7 and potentially 10-14)
- **Total Hardware Cost**: $230-$360 (1-2 replacements)
- **Total Power Cost**: $48 (10 years)
- **Total Software Cost**: $0
- **10-Year Total Cost**: $278-$408
- **Annual Operating Cost**: $27.80-$40.80/year

**Sustainability Metrics:**
- **Power Efficiency**: 87-93% better than mini PC alternatives
- **Cost Efficiency**: 50-70% lower than cloud subscription alternatives over 10 years
- **Environmental Impact**: Minimal (16 kg CO2/year, small electronic waste footprint)
- **Maintenance Efficiency**: Low maintenance requirements, easy troubleshooting

**Conclusion:**

The sustainability trade-off demonstrates that the Raspberry Pi 4B solution achieves high sustainability (8/10) through exceptional power efficiency, adequate lifespan, cost-effective operation, and low environmental impact. The system provides long-term viability while maintaining full functionality, making it a sustainable choice for home parental control systems over a 5-10 year period.

## Summary

This chapter has analyzed five critical design trade-offs that shaped the Child-Centric WiFi Monitoring and Control System:

1. **Economic Trade-Off**: Selected low-cost Raspberry Pi 4B ($115-$120) over expensive alternatives, achieving 50-70% cost savings over 5-10 years compared to cloud subscriptions while maintaining functionality.

2. **Safety/Cybersecurity Trade-Off**: Achieved moderate cybersecurity risk score (4/10) through local data storage, multi-layered security architecture, and optional secure remote access, prioritizing data privacy over cloud convenience.

3. **Risk/Failure Rate Trade-Off**: Accepted medium failure risk (5/10) in exchange for cost-effectiveness, implementing multiple mitigation strategies including proper cooling, service monitoring, and comprehensive error handling.

4. **Environmental Trade-Off**: Achieved low environmental impact (2/10) through minimal energy consumption (32 kWh/year), small carbon footprint (16 kg CO2/year), and compact design, providing 87-93% energy savings compared to alternatives.

5. **Sustainability Trade-Off**: Achieved high sustainability score (8/10) through exceptional power efficiency (3-7W), adequate lifespan (5-7 years), cost-effective operation ($254-$264 over 5 years), and low environmental impact.

These trade-offs collectively demonstrate that the selected design approach balances competing priorities effectively, providing a cost-effective, secure, reliable, environmentally conscious, and sustainable solution for home parental control systems. The analysis shows that no single alternative dominates across all criteria, but the Raspberry Pi 4B-based solution provides the best overall balance for the project's constraints and requirements.

## 3.3 Summary of the Normalized Values of the Design

To facilitate comparison and analysis across the five trade-off categories, the design scores have been normalized to a common 0-10 scale. This normalization allows for direct comparison of performance across different evaluation criteria and provides insight into the overall design balance.

### Normalized Trade-Off Scores

The following table presents the normalized scores for each trade-off category, where higher scores indicate better performance (lower cost, lower risk, lower environmental impact, higher sustainability):

| Trade-Off Category | Raw Score | Normalized Score (0-10) | Interpretation |
|-------------------|-----------|-------------------------|----------------|
| **Economic (Cost Effectiveness)** | N/A | **9.0/10** | Excellent - Low initial cost ($115-$120), zero recurring costs, 50-70% savings vs. alternatives |
| **Safety (Cybersecurity Risk)** | 4/10 (risk) | **6.0/10** | Moderate - Medium risk (4/10) converted to safety score (6/10), balanced security with privacy |
| **Risk (Failure Rate)** | 5/10 (risk) | **5.0/10** | Moderate - Medium failure risk (5/10), acceptable for home use with mitigation strategies |
| **Environmental (Impact)** | 2/10 (impact) | **8.0/10** | Excellent - Low environmental impact (2/10) converted to performance score (8/10), minimal energy and waste |
| **Sustainability** | 8/10 | **8.0/10** | Excellent - High sustainability, exceptional power efficiency, adequate lifespan |

**Note on Score Interpretation:**
- For risk-based metrics (Safety, Risk), lower raw risk scores translate to higher normalized performance scores
- For impact-based metrics (Environmental), lower raw impact scores translate to higher normalized performance scores
- Economic and Sustainability scores are directly performance-based (higher is better)

### Overall Design Performance

The normalized scores reveal the design's strengths and areas of balanced compromise:

**Strengths (Score ≥ 8.0):**
- **Economic Performance**: 9.0/10 - Exceptional cost-effectiveness
- **Environmental Performance**: 8.0/10 - Minimal environmental impact
- **Sustainability**: 8.0/10 - High long-term viability

**Balanced Areas (Score 5.0-7.9):**
- **Safety/Cybersecurity**: 6.0/10 - Moderate security with strong privacy protection
- **Risk/Failure Rate**: 5.0/10 - Acceptable reliability for home use

**Weighted Average Performance**: 7.2/10

The normalized values demonstrate that the design excels in economic, environmental, and sustainability considerations while maintaining acceptable levels of security and reliability. This balance aligns with the project's constraints and target user requirements, prioritizing cost-effectiveness and environmental responsibility without compromising essential security and reliability features.

## 3.4 Designers' Raw Ranking for the Design

During the design process, the development team evaluated and ranked the relative importance of each trade-off category based on project constraints, user requirements, and engineering priorities. This section presents the designers' raw rankings and their rationale.

### Ranking Methodology

The design team assigned importance rankings to each trade-off category on a scale of 1-10, where:
- **10**: Critical - Must be optimized, cannot be compromised
- **8-9**: Very Important - High priority, significant influence on design decisions
- **6-7**: Important - Moderate priority, considered in design decisions
- **4-5**: Moderate - Lower priority, acceptable trade-offs
- **1-3**: Low Priority - Minimal influence on design decisions

### Designers' Raw Rankings

| Trade-Off Category | Importance Ranking | Rationale |
|-------------------|-------------------|-----------|
| **Economic (Cost)** | **10/10** | Critical - Project budget constraints require cost-effective solution. System must be accessible to families with limited financial resources. Low initial cost and zero recurring fees are essential for adoption. |
| **Safety (Cybersecurity)** | **8/10** | Very Important - System handles sensitive children's browsing data. Must protect privacy and prevent unauthorized access. However, local storage reduces some security risks compared to cloud solutions. |
| **Risk (Failure Rate)** | **7/10** | Important - System reliability is important for parental trust, but some failure risk is acceptable for home use. Cost-effective mitigation strategies are prioritized over expensive redundancy. |
| **Environmental (Impact)** | **6/10** | Important - Environmental responsibility is valued, but not the primary driver. Low power consumption is beneficial for both environmental and cost reasons. |
| **Sustainability** | **7/10** | Important - Long-term viability is important for system adoption. However, cost-effectiveness takes precedence over maximum lifespan. |

### Ranking Analysis

The designers' rankings reveal clear priorities:

1. **Economic considerations dominate** (10/10) - Budget constraints and accessibility requirements make cost the primary design driver
2. **Security is highly valued** (8/10) - Data privacy and protection are critical, especially for children's data
3. **Reliability and sustainability are balanced** (7/10 each) - Important but secondary to cost and security
4. **Environmental impact is considered** (6/10) - Valued but not a primary constraint

### Influence on Design Decisions

These rankings directly influenced key design choices:

- **Hardware Selection**: Raspberry Pi 4B chosen primarily for cost (10/10 priority) while maintaining adequate performance
- **Local Storage**: Selected over cloud to prioritize security (8/10) and cost (10/10)
- **Single Hardware Setup**: Chosen over redundant systems due to cost priority (10/10) over maximum reliability (7/10)
- **Open-Source Software**: Selected for cost (10/10) and sustainability (7/10) benefits
- **Energy Efficiency**: Optimized for both environmental (6/10) and cost (10/10) considerations

The raw rankings demonstrate that economic constraints were the primary driver, while security, reliability, and sustainability were important secondary considerations that shaped the final design approach.

## 3.5 Influence of the Design Trade-Off in the Final Design

The trade-off analysis directly influenced numerous design decisions throughout the system development. This section examines how each trade-off category shaped specific aspects of the final design, demonstrating the practical impact of the trade-off evaluation process.

### 3.5.1 Economic Trade-Off Influence

The economic trade-off (ranked 10/10 importance) had the most significant influence on the final design:

**Hardware Selection:**
- **Decision**: Raspberry Pi 4B with 480GB SSD ($115-$120 total)
- **Influence**: Cost constraint eliminated expensive alternatives (mini PC $300-$800, cloud subscriptions $600-$1,800 over 5 years)
- **Result**: System accessible to families with limited budgets, one-time investment vs. recurring costs

**Software Stack Selection:**
- **Decision**: Entirely open-source software stack (Laravel, MariaDB, Nginx, NoDogSplash)
- **Influence**: Zero software licensing fees required to meet budget constraints
- **Result**: $0 software costs, no recurring subscription fees

**Storage Solution:**
- **Decision**: 480GB SSD instead of larger capacity or cloud storage
- **Influence**: Balance between cost ($40) and sufficient capacity for videos and logs
- **Result**: Adequate storage at minimal cost, local storage eliminates cloud fees

**Architecture Simplification:**
- **Decision**: Single Raspberry Pi handling all functions (AP, web server, firewall, monitoring)
- **Influence**: Avoid additional hardware costs for separate components
- **Result**: Cost-effective all-in-one solution, simplified deployment

### 3.5.2 Safety/Cybersecurity Trade-Off Influence

The safety trade-off (ranked 8/10 importance) influenced security architecture and data handling:

**Data Storage Architecture:**
- **Decision**: Local data storage on Raspberry Pi, no cloud transmission
- **Influence**: Prioritize data privacy (1/10 risk) over cloud convenience (7/10 risk)
- **Result**: Children's browsing data never leaves home network, eliminates cloud data breach risks

**Network Security Implementation:**
- **Decision**: Three-tier network control architecture (NetworkService → ScriptExecutor → Shell Scripts)
- **Influence**: Multi-layered security (4/10 risk) balances functionality with protection
- **Result**: Whitelist validation, path validation, argument sanitization prevent unauthorized access

**Remote Access Design:**
- **Decision**: Optional secure remote access (VPN, cloud tunneling) rather than mandatory cloud service
- **Influence**: Flexible security (4/10 risk) allows parents to choose security level
- **Result**: System works offline, secure remote access optional based on user needs

**Application Security:**
- **Decision**: OWASP Top Ten guidelines, CSRF protection, secure session management
- **Influence**: Moderate security (4/10 risk) provides adequate protection for home use
- **Result**: Protection against common vulnerabilities while maintaining usability

### 3.5.3 Risk/Failure Rate Trade-Off Influence

The risk trade-off (ranked 7/10 importance) influenced reliability and mitigation strategies:

**Hardware Reliability Approach:**
- **Decision**: Single Raspberry Pi 4B with proper cooling and quality power supply
- **Influence**: Acceptable failure risk (5/10) in exchange for cost-effectiveness
- **Result**: 5-7 year estimated lifespan, cost-effective replacement if needed

**Mitigation Strategy Implementation:**
- **Decision**: Multiple mitigation strategies (cooling, monitoring, backups) rather than hardware redundancy
- **Influence**: Balance reliability (5/10 risk) with cost (10/10 priority)
- **Result**: Service monitoring, automatic restarts, database backups minimize failure impact

**Software Reliability Design:**
- **Decision**: Comprehensive error handling, queue worker monitoring, automatic job retry
- **Influence**: Software reliability (5/10 risk) acceptable with proper error handling
- **Result**: System continues functioning during minor failures, automatic recovery mechanisms

**Offline Operation Capability:**
- **Decision**: System designed for offline operation, local dashboard always accessible
- **Influence**: Internet connectivity failure (high probability) should not disable system
- **Result**: System functions during internet outages, only remote access affected

### 3.5.4 Environmental Trade-Off Influence

The environmental trade-off (ranked 6/10 importance) influenced energy efficiency and resource usage:

**Hardware Selection for Energy Efficiency:**
- **Decision**: Raspberry Pi 4B (3-7W) instead of mini PC (30-100W)
- **Influence**: Low environmental impact (2/10) and cost (10/10) both favor low-power hardware
- **Result**: 32 kWh/year consumption, 87-93% less energy than alternatives

**Storage Solution Selection:**
- **Decision**: SSD instead of microSD for better reliability and efficiency
- **Influence**: Environmental (2/10 impact) and reliability (5/10 risk) considerations
- **Result**: Better wear-leveling, suitable for continuous operation, minimal waste

**Software Optimization:**
- **Decision**: Lightweight software stack (Raspberry Pi OS Lite, optimized Laravel)
- **Influence**: Minimize resource usage for both environmental (2/10 impact) and performance reasons
- **Result**: Efficient operation, minimal energy consumption

**Local Processing Architecture:**
- **Decision**: All processing local, no cloud data transmission
- **Influence**: Eliminate cloud infrastructure energy consumption
- **Result**: Reduced network transmission energy, lower overall environmental impact

### 3.5.5 Sustainability Trade-Off Influence

The sustainability trade-off (ranked 7/10 importance) influenced long-term viability decisions:

**Power Consumption Optimization:**
- **Decision**: Extremely low power consumption (3-7W) prioritized
- **Influence**: High sustainability (8/10) requires efficient operation
- **Result**: 32 kWh/year, minimal operating costs, long-term cost sustainability

**Hardware Lifespan Consideration:**
- **Decision**: Quality components with proper cooling for 5-7 year lifespan
- **Influence**: Sustainability (8/10) requires adequate lifespan without excessive cost
- **Result**: Balance between lifespan and cost, cost-effective replacement if needed

**Software Sustainability:**
- **Decision**: Open-source software stack for long-term availability
- **Influence**: Sustainability (8/10) requires software that remains available and updatable
- **Result**: No vendor lock-in, community support, regular updates available

**Component Replaceability:**
- **Decision**: Individual components (SSD, power supply) can be replaced
- **Influence**: Sustainability (8/10) requires maintainable system
- **Result**: Extend system lifespan, reduce waste, cost-effective maintenance

### 3.5.6 Integrated Influence Summary

The trade-offs collectively influenced the final design in several integrated ways:

**Cost-Security Balance:**
- Local storage chosen for both cost (10/10) and security (8/10) benefits
- Open-source software selected for cost (10/10) and security (8/10) transparency

**Cost-Reliability Balance:**
- Single hardware setup chosen for cost (10/10) with acceptable reliability (7/10)
- Mitigation strategies implemented to improve reliability without increasing cost

**Environmental-Cost Synergy:**
- Low-power hardware benefits both environmental (6/10) and cost (10/10) objectives
- Local processing reduces both environmental impact and operational costs

**Sustainability-Cost Alignment:**
- Long lifespan and low power consumption support both sustainability (7/10) and cost (10/10)
- Open-source software ensures sustainability while maintaining zero software costs

The trade-off analysis demonstrates that design decisions were not made in isolation, but rather considered multiple trade-off categories simultaneously, resulting in a cohesive design that balances competing priorities effectively.

## 3.6 Sensitivity Analysis

Sensitivity analysis evaluates how changes in the relative importance (weighting) of different trade-off categories would affect the overall design evaluation. This analysis tests the robustness of the design decision by examining whether the Raspberry Pi 4B solution remains optimal under different priority scenarios.

### 3.6.1 Sensitivity Analysis Methodology

The sensitivity analysis uses weighted scoring, where each trade-off category is assigned a weight based on its importance ranking. The overall design score is calculated as:

**Overall Score = Σ (Normalized Score × Weight) / Σ Weights**

Where:
- Normalized Scores: Economic (9.0), Safety (6.0), Risk (5.0), Environmental (8.0), Sustainability (8.0)
- Weights: Sum to 10 for each iteration (representing total importance points)

Different iterations test various priority scenarios to determine design sensitivity to priority changes.

### 3.6.2 Iteration 1: Economic Priority (10-9-8-7-6)

This iteration represents the designers' original ranking, where economic considerations are most critical:

**Weighting:**
- Economic: 10
- Safety: 8
- Risk: 7
- Environmental: 6
- Sustainability: 7
- **Total: 38** (normalized to sum of 10)

**Normalized Weights:**
- Economic: 2.63 (10/38 × 10)
- Safety: 2.11 (8/38 × 10)
- Risk: 1.84 (7/38 × 10)
- Environmental: 1.58 (6/38 × 10)
- Sustainability: 1.84 (7/38 × 10)

**Weighted Score Calculation:**
- Economic: 9.0 × 2.63 = 23.67
- Safety: 6.0 × 2.11 = 12.66
- Risk: 5.0 × 1.84 = 9.20
- Environmental: 8.0 × 1.58 = 12.64
- Sustainability: 8.0 × 1.84 = 14.72
- **Total: 72.89**
- **Overall Score: 7.29/10**

**Analysis:**
This iteration reflects the actual design priorities. The high economic weight (2.63) leverages the system's strongest performance area (9.0/10), resulting in a strong overall score of 7.29/10. The design is well-suited to this priority distribution.

### 3.6.3 Iteration 2: Balanced Priorities (6-7-8-9-10)

This iteration tests a scenario where priorities are reversed, with sustainability and environmental concerns most important:

**Weighting:**
- Economic: 6
- Safety: 7
- Risk: 8
- Environmental: 9
- Sustainability: 10
- **Total: 40** (normalized to sum of 10)

**Normalized Weights:**
- Economic: 1.50 (6/40 × 10)
- Safety: 1.75 (7/40 × 10)
- Risk: 2.00 (8/40 × 10)
- Environmental: 2.25 (9/40 × 10)
- Sustainability: 2.50 (10/40 × 10)

**Weighted Score Calculation:**
- Economic: 9.0 × 1.50 = 13.50
- Safety: 6.0 × 1.75 = 10.50
- Risk: 5.0 × 2.00 = 10.00
- Environmental: 8.0 × 2.25 = 18.00
- Sustainability: 8.0 × 2.50 = 20.00
- **Total: 72.00**
- **Overall Score: 7.20/10**

**Analysis:**
Even with reversed priorities, the design maintains a strong overall score (7.20/10). The system's excellent performance in environmental (8.0/10) and sustainability (8.0/10) categories compensates for reduced economic weighting. The design remains competitive under this priority scenario.

### 3.6.4 Iteration 3: Security Priority (9-10-8-7-6)

This iteration tests a scenario where security is the highest priority:

**Weighting:**
- Economic: 9
- Safety: 10
- Risk: 8
- Environmental: 7
- Sustainability: 6
- **Total: 40** (normalized to sum of 10)

**Normalized Weights:**
- Economic: 2.25 (9/40 × 10)
- Safety: 2.50 (10/40 × 10)
- Risk: 2.00 (8/40 × 10)
- Environmental: 1.75 (7/40 × 10)
- Sustainability: 1.50 (6/40 × 10)

**Weighted Score Calculation:**
- Economic: 9.0 × 2.25 = 20.25
- Safety: 6.0 × 2.50 = 15.00
- Risk: 5.0 × 2.00 = 10.00
- Environmental: 8.0 × 1.75 = 14.00
- Sustainability: 8.0 × 1.50 = 12.00
- **Total: 71.25**
- **Overall Score: 7.13/10**

**Analysis:**
With security as the highest priority, the overall score decreases slightly (7.13/10) due to the system's moderate security performance (6.0/10). However, the design remains competitive because strong performance in other areas (economic, environmental, sustainability) compensates for the security weighting.

### 3.6.5 Iteration 4: Reliability Priority (8-7-10-6-9)

This iteration tests a scenario where reliability (risk minimization) is the highest priority:

**Weighting:**
- Economic: 8
- Safety: 7
- Risk: 10
- Environmental: 6
- Sustainability: 9
- **Total: 40** (normalized to sum of 10)

**Normalized Weights:**
- Economic: 2.00 (8/40 × 10)
- Safety: 1.75 (7/40 × 10)
- Risk: 2.50 (10/40 × 10)
- Environmental: 1.50 (6/40 × 10)
- Sustainability: 2.25 (9/40 × 10)

**Weighted Score Calculation:**
- Economic: 9.0 × 2.00 = 18.00
- Safety: 6.0 × 1.75 = 10.50
- Risk: 5.0 × 2.50 = 12.50
- Risk penalty: Lower score (5.0/10) with high weight (2.50) reduces overall performance
- Environmental: 8.0 × 1.50 = 12.00
- Sustainability: 8.0 × 2.25 = 18.00
- **Total: 71.00**
- **Overall Score: 7.10/10**

**Analysis:**
With reliability as the highest priority, the overall score decreases (7.10/10) because the system's moderate reliability performance (5.0/10) receives high weighting. However, the design remains viable because excellent performance in economic, environmental, and sustainability categories provides compensation.

### 3.6.6 Iteration 5: Equal Priorities (2-2-2-2-2)

This iteration tests a scenario where all trade-offs are equally important:

**Weighting:**
- Economic: 2
- Safety: 2
- Risk: 2
- Environmental: 2
- Sustainability: 2
- **Total: 10** (already normalized)

**Normalized Weights:**
- All categories: 2.00 each

**Weighted Score Calculation:**
- Economic: 9.0 × 2.00 = 18.00
- Safety: 6.0 × 2.00 = 12.00
- Risk: 5.0 × 2.00 = 10.00
- Environmental: 8.0 × 2.00 = 16.00
- Sustainability: 8.0 × 2.00 = 16.00
- **Total: 72.00**
- **Overall Score: 7.20/10**

**Analysis:**
With equal priorities, the overall score is 7.20/10, representing a balanced evaluation. The design performs well across all categories, with strengths in economic, environmental, and sustainability areas compensating for moderate performance in security and reliability.

### 3.6.7 Sensitivity Analysis Summary

| Iteration | Priority Focus | Economic Weight | Safety Weight | Risk Weight | Env. Weight | Sust. Weight | Overall Score |
|-----------|----------------|----------------|---------------|-------------|-------------|--------------|---------------|
| 1 | Economic (Original) | 2.63 | 2.11 | 1.84 | 1.58 | 1.84 | **7.29/10** |
| 2 | Sustainability/Environmental | 1.50 | 1.75 | 2.00 | 2.25 | 2.50 | **7.20/10** |
| 3 | Security | 2.25 | 2.50 | 2.00 | 1.75 | 1.50 | **7.13/10** |
| 4 | Reliability | 2.00 | 1.75 | 2.50 | 1.50 | 2.25 | **7.10/10** |
| 5 | Equal Priorities | 2.00 | 2.00 | 2.00 | 2.00 | 2.00 | **7.20/10** |

**Score Range**: 7.10 - 7.29 (variation of 0.19 points, 2.6% variation)

### 3.6.8 Conclusion of Sensitivity Analysis

The sensitivity analysis reveals several important insights about the design's robustness:

**1. Design Stability:**
The overall design score remains consistently high (7.10-7.29/10) across all priority scenarios, demonstrating that the Raspberry Pi 4B solution is robust to changes in trade-off priorities. The narrow score range (0.19 points, 2.6% variation) indicates that the design decision is not highly sensitive to priority weighting changes.

**2. Optimal Performance Scenario:**
The design performs best (7.29/10) under the original economic-priority scenario, which aligns with the project's actual constraints and requirements. This confirms that the design decision was appropriate for the intended use case.

**3. Competitive Under All Scenarios:**
Even under priority scenarios that emphasize the design's weaker areas (security 6.0/10, reliability 5.0/10), the overall score remains above 7.0/10. This demonstrates that strong performance in economic (9.0/10), environmental (8.0/10), and sustainability (8.0/10) categories provides sufficient compensation.

**4. Balanced Design Strength:**
The design's balanced performance across multiple categories ensures competitiveness regardless of priority distribution. No single weak area significantly undermines the overall evaluation.

**5. Design Decision Validation:**
The sensitivity analysis validates that the Raspberry Pi 4B selection was appropriate. The design remains competitive even when priorities shift away from its strongest areas (economic, environmental, sustainability), demonstrating that the solution provides a robust balance across all trade-off categories.

**6. Practical Implications:**
The low sensitivity (2.6% variation) suggests that the design decision would likely remain optimal even if project priorities change slightly over time. This provides confidence that the system will remain appropriate as requirements evolve.

**Conclusion:**
The sensitivity analysis confirms that the selected design approach is robust and well-balanced. The Raspberry Pi 4B solution maintains strong overall performance (7.10-7.29/10) across diverse priority scenarios, validating that the design decision effectively balances competing trade-offs while remaining optimal for the project's constraints and requirements. The design's stability under varying priorities demonstrates its suitability for the intended home parental control application.

## References

- European Union (2016). *General Data Protection Regulation (GDPR)*. Regulation (EU) 2016/679. Official Journal of the European Union. https://eur-lex.europa.eu/legal-content/EN/TXT/?uri=CELEX:32016R0679  
  *Cited in: Section 3.2.2 (Safety/Cybersecurity Trade-Off) - Data privacy principles and compliance discussion*

- IEEE (2018). *IEEE Standard for Ethernet*. IEEE Std 802.3-2018. Institute of Electrical and Electronics Engineers. https://standards.ieee.org/standard/802_3-2018.html  
  *Cited in: Section 3.1 (Summary of Constraints) - Technical constraints and network infrastructure standards*

- IEEE (2020). *IEEE Standard for Information Technology--Telecommunications and Information Exchange between Systems--Local and Metropolitan Area Networks--Specific Requirements--Part 11: Wireless LAN Medium Access Control (MAC) and Physical Layer (PHY) Specifications*. IEEE Std 802.11-2020. Institute of Electrical and Electronics Engineers. https://standards.ieee.org/standard/802_11-2020.html  
  *Cited in: Section 3.2.3 (Risk/Failure Rate Trade-Off) - WiFi protocol standards for network reliability mitigation*

- IETF (1987). *Domain Names - Implementation and Specification*. RFC 1035. Internet Engineering Task Force. https://www.rfc-editor.org/rfc/rfc1035  
  *Cited in: Section 3.1 (Summary of Constraints) - DNS protocol standards for domain-level content filtering*

- IETF (1997). *Dynamic Host Configuration Protocol*. RFC 2131. Internet Engineering Task Force. https://www.rfc-editor.org/rfc/rfc2131  
  *Cited in: Section 3.1 (Summary of Constraints) - DHCP protocol for automatic IP address assignment*

- IETF (2018). *The Transport Layer Security (TLS) Protocol Version 1.3*. RFC 8446. Internet Engineering Task Force. https://www.rfc-editor.org/rfc/rfc8446  
  *Cited in: Section 3.1 (Summary of Constraints) and Section 3.2.2 (Safety/Cybersecurity Trade-Off) - HTTPS/TLS encryption standards for secure remote access*

- ISO/IEC (2011). *Systems and software engineering -- Systems and software Quality Requirements and Evaluation (SQuaRE) -- System and software quality models*. ISO/IEC 25010:2011. International Organization for Standardization. https://www.iso.org/standard/35733.html  
  *Cited in: Section 3.1 (Summary of Constraints) - Software quality standards for system reliability and maintainability*

- Laravel (2024). *Laravel - The PHP Framework for Web Artisans*. Laravel Framework Documentation. https://laravel.com/docs  
  *Cited in: Section 3.2.1 (Economic Trade-Off) - Software stack cost analysis; Section 3.2.3 (Risk/Failure Rate Trade-Off) - Application reliability discussion; Section 3.5.1 (Economic Trade-Off Influence) - Software stack selection; Section 3.5.2 (Safety/Cybersecurity Trade-Off Influence) - Application security implementation*

- MariaDB Foundation (2024). *MariaDB Server*. MariaDB Documentation. https://mariadb.org/documentation/  
  *Cited in: Section 3.2.1 (Economic Trade-Off) - Database software cost analysis; Section 3.2.3 (Risk/Failure Rate Trade-Off) - Database reliability and corruption risk discussion*

- Nginx (2024). *Nginx - High Performance Load Balancer, Web Server & Reverse Proxy*. Nginx Documentation. https://nginx.org/en/docs/  
  *Cited in: Section 3.2.1 (Economic Trade-Off) - Web server software cost analysis; Section 3.5.1 (Economic Trade-Off Influence) - Software stack selection*

- NoDogSplash Community (2024). *NoDogSplash - Open Source Captive Portal*. NoDogSplash GitHub Repository. https://github.com/nodogsplash/nodogsplash  
  *Cited in: Section 3.2.1 (Economic Trade-Off) - Captive portal software cost analysis; Section 3.2.3 (Risk/Failure Rate Trade-Off) - Network service failure risk discussion; Section 3.5.1 (Economic Trade-Off Influence) - Software stack selection*

- OWASP (2021). *OWASP Top Ten - The Ten Most Critical Web Application Security Risks*. Open Web Application Security Project. https://owasp.org/www-project-top-ten/  
  *Cited in: Section 3.2.2 (Safety/Cybersecurity Trade-Off) - Application-level security implementation and cybersecurity risk score breakdown; Section 3.5.2 (Safety/Cybersecurity Trade-Off Influence) - Security best practices implementation*

- OWASP (2024). *OWASP - Open Web Application Security Project*. OWASP Foundation. https://owasp.org/  
  *Cited in: Section 3.2.2 (Safety/Cybersecurity Trade-Off) - Security guidelines and best practices framework*

- Raspberry Pi Foundation (2024). *Raspberry Pi 4 Model B*. Raspberry Pi Documentation. https://www.raspberrypi.com/products/raspberry-pi-4-model-b/  
  *Cited in: Throughout Chapter 3 - Primary hardware platform selection and analysis across all trade-off categories (Economic, Safety, Risk, Environmental, Sustainability); Sections 3.2.1, 3.2.3, 3.2.4, 3.2.5, 3.3, 3.4, 3.5, 3.6 - Hardware selection rationale and performance evaluation*

- Raspberry Pi Foundation (2024). *Raspberry Pi OS*. Raspberry Pi Documentation. https://www.raspberrypi.com/software/  
  *Cited in: Section 3.2.1 (Economic Trade-Off) - Operating system cost analysis; Section 3.2.3 (Risk/Failure Rate Trade-Off) - Operating system reliability discussion; Section 3.2.4 (Environmental Trade-Off) - Software optimization for energy efficiency; Section 3.5.4 (Environmental Trade-Off Influence) - Lightweight software stack selection*

- UNICEF (2023). *Keeping children safe online*. United Nations International Children's Emergency Fund. https://www.unicef.org/protection/keeping-children-safe-online  
  *Cited in: Section 3.1 (Summary of Constraints) - Problem context and security/privacy constraints for children's online safety*

- W3C (2021). *World Wide Web Consortium (W3C) Standards*. World Wide Web Consortium. https://www.w3.org/standards/  
  *Cited in: Section 3.1 (Summary of Constraints) - Web standards for browser compatibility and frontend development*

