'''
Created on 11 may. 2021

@author: user
'''
from django.test import TestCase
import datetime
from hive.timeframe import TimeFrame
from hive.timeframeslist import TimeFramesList

class TestTimeFrameList(TestCase):


    def setUp(self):
        self.t0 = datetime.datetime.now()
        self.t1 =  self.t0 + datetime.timedelta(days=1)
        self.t2 =  self.t1 + datetime.timedelta(days=1) 
        self.t3 =  self.t2 + datetime.timedelta(days=1)
        self.timeframe0_1 = TimeFrame(self.t0, self.t1)
        self.timeframe1_2 = TimeFrame(self.t1, self.t2)
        self.timeframe2_3 = TimeFrame(self.t2, self.t3)
        self.timeframe0_2 = TimeFrame(self.t0, self.t2)
        self.timeframe1_3 = TimeFrame(self.t1, self.t3)
        self.timeframe0_3 = TimeFrame(self.t0, self.t3)
        self.ttfl = TimeFramesList()
        self.ttfl_empty = TimeFramesList()
        self.ttfl.addTimeFrame(self.timeframe0_1)
        self.ttfl.addTimeFrame(self.timeframe1_2)
        self.ttfl.addTimeFrame(self.timeframe2_3)
        self.ttfl.addTimeFrame(self.timeframe0_2)
        self.ttfl.addTimeFrame(self.timeframe1_3)
        self.ttfl.addTimeFrame(self.timeframe0_3)


    def tearDown(self):
        pass


    def testAddTimeFramesList(self):
        self.ttfl_empty.addTimeFrame(self.timeframe0_1)
        self.ttfl_empty.addTimeFrame(self.timeframe1_2)
        self.ttfl_empty.addTimeFrame(self.timeframe2_3)
        self.ttfl_empty.addTimeFrame(self.timeframe0_2)
        self.ttfl_empty.addTimeFrame(self.timeframe1_3)
        self.ttfl_empty.addTimeFrame(self.timeframe0_3)
        self.assertEqual( self.ttfl_empty.size(), 6)
        

    def testRemoveTimeFramesList(self):
        self.ttfl.removeTimeFrame(self.timeframe0_2)
        self.assertEqual( self.ttfl.size(), 5)
        self.ttfl.removeTimeFrame(self.timeframe0_2)
        self.assertEqual( self.ttfl.size(), 5)
        self.ttfl.removeTimeFrame(self.timeframe0_3)
        self.assertEqual( self.ttfl.size(), 4)
     
     
    def testIsOverlappingTimeFramesList(self):
        self.assertEqual( self.ttfl_empty.isOverlapping(), False) 
        self.ttfl_empty.addTimeFrame(self.timeframe0_1)
        self.ttfl_empty.addTimeFrame(self.timeframe0_3)
        self.assertEqual( self.ttfl_empty.isOverlapping(), True)
        self.assertEqual( self.ttfl.isOverlapping(), True)
        
        
    def testJoinOverlappingTimeFramesList(self):
        self.ttfl_empty.joinOverlapping()
        self.assertEqual( self.ttfl_empty.size(), 0)
        self.ttfl_empty.addTimeFrame(self.timeframe0_1)
        self.ttfl_empty.addTimeFrame(self.timeframe2_3)
        self.ttfl_empty.joinOverlapping()
        self.assertEqual( self.ttfl_empty.size(), 2)
        self.ttfl_empty.addTimeFrame(self.timeframe1_2)
        self.ttfl_empty.joinOverlapping()
        self.assertEqual( self.ttfl_empty.size(), 1)
        self.ttfl.joinOverlapping()
        self.assertEqual( self.ttfl.size(), 1)
        